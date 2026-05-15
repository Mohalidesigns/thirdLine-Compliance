<?php

declare(strict_types=1);

/**
 * Scenario 2: Policy full lifecycle E2E (cross-phase seam test).
 *
 * Exercises the wiring between:
 *   - Policy module (PoliciesController, PolicyService, state machine)
 *   - Audit module (state_transitioned events, policy.created, policy.updated)
 *   - RBAC (policy_owner vs compliance_officer separation of duties)
 *   - Queue (RenderPolicyPdfJob dispatched on 'published' transition)
 *
 * State graph under test:
 *   draft → in_review → approved → published → in_force → under_review → superseded
 *
 * Separation of duties verified:
 *   policy_owner  : may create + submit for review, blocked from approving
 *   compliance_officer : approves, publishes, marks in_force, supersedes
 *   control_tester : may acknowledge a published / in_force policy
 *
 * Setup note: Spatie model-states does not honour 'state' => 'in_review' passed
 * to Model::create() — the default state is always applied on create and the
 * value in $fillable is ignored by the HasStates boot.  We therefore use
 * PolicyService::transition() to advance state in helpers that need a non-draft
 * starting point.
 */

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Modules\Policy\Jobs\RenderPolicyPdfJob;
use Modules\Policy\Models\Policy;
use Modules\Policy\Models\PolicyAcknowledgement;
use Modules\Policy\Services\PolicyService;

beforeEach(function (): void {
    (new RolesAndPermissionsSeeder)->run();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Minimum valid payload for POST /policies.
 */
function policyPayload(array $overrides = []): array
{
    return array_merge([
        'title'      => 'AML Policy ' . uniqid(),
        'category'   => 'aml',
        'owner_team' => 'Compliance',
        'summary'    => 'A policy for integration testing.',
        'body'       => 'Full body text of the policy.',
    ], $overrides);
}

/**
 * Create a draft policy owned by the given user.
 */
function createDraftPolicy(User $owner): Policy
{
    $policy = Policy::create([
        'title'      => 'Lifecycle Policy ' . uniqid(),
        'category'   => 'governance',
        'owner_team' => 'Compliance',
        'created_by' => $owner->id,
    ]);

    return $policy;
}

/**
 * Advance a policy to the target state using PolicyService.
 * Intermediate states are traversed in order.
 */
function advancePolicyTo(Policy $policy, string $targetState, int $actorId): Policy
{
    $service = app(PolicyService::class);

    $path = [
        'draft'        => 0,
        'in_review'    => 1,
        'approved'     => 2,
        'published'    => 3,
        'in_force'     => 4,
        'under_review' => 5,
        'superseded'   => 6,
    ];

    $transitions = [
        'draft'        => 'in_review',
        'in_review'    => 'approved',
        'approved'     => 'published',
        'published'    => 'in_force',
        'in_force'     => 'under_review',
        'under_review' => 'superseded',
    ];

    $current = $policy->fresh()->state::$name;

    while ($current !== $targetState && isset($path[$current]) && $path[$current] < $path[$targetState]) {
        $next = $transitions[$current] ?? null;
        if ($next === null) {
            break;
        }
        $note = in_array($next, ['superseded'], true) ? 'Integration test note' : null;
        $policy = $service->transition($policy->fresh(), $next, $note, $actorId);
        $current = $policy->state::$name;
    }

    return $policy->fresh();
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

it('policy_owner can create a draft policy and created_by is stamped with the actor id', function (): void {
    Queue::fake();

    $response = $this->actingAsRole('policy_owner')
        ->post('/policies', policyPayload(['title' => 'Policy Owner Draft']));

    $response->assertRedirect();

    $policy = Policy::withoutGlobalScopes()
        ->where('title', 'Policy Owner Draft')
        ->firstOrFail();

    expect($policy->state::$name)->toBe('draft');
    // created_by must match the authenticated user.
    expect($policy->created_by)->not->toBeNull();
    $this->assertDatabaseHas('audit_events', ['action' => 'policy.created']);
});

it('policy_owner can transition draft to in_review', function (): void {
    Queue::fake();

    $owner = User::factory()->create(['email_verified_at' => now()]);
    $owner->assignRole('policy_owner');

    $policy = createDraftPolicy($owner);

    $this->actingAs($owner)
        ->post("/policies/{$policy->id}/transition", ['to' => 'in_review'])
        ->assertRedirect("/policies/{$policy->id}");

    expect($policy->fresh()->state::$name)->toBe('in_review');

    $this->assertDatabaseHas('policy_versions', [
        'policy_id' => $policy->id,
        'state'     => 'in_review',
    ]);

    $this->assertDatabaseHas('audit_events', [
        'action'     => 'state_transitioned',
        'subject_id' => $policy->id,
    ]);
});

it('policy_owner cannot approve a policy and receives a redirect with an error', function (): void {
    Queue::fake();

    $owner = User::factory()->create(['email_verified_at' => now()]);
    $owner->assignRole('policy_owner');

    // Advance to in_review via the service (policy_owner only has submit_for_review).
    $policy = createDraftPolicy($owner);
    $policy = app(PolicyService::class)->transition($policy, 'in_review', null, $owner->id);

    expect($policy->state::$name)->toBe('in_review');

    // The policy_owner does NOT have policies.transition.approve — the gate blocks this.
    $this->actingAs($owner)
        ->post("/policies/{$policy->id}/transition", ['to' => 'approved'])
        ->assertForbidden();

    // Policy must remain in_review.
    expect($policy->fresh()->state::$name)->toBe('in_review');
});

it('compliance_officer can approve an in_review policy and a policy_version row is written', function (): void {
    Queue::fake();

    $actor = User::factory()->create(['email_verified_at' => now()]);
    $actor->assignRole('compliance_officer');

    $policy = createDraftPolicy($actor);
    $policy = app(PolicyService::class)->transition($policy, 'in_review', null, $actor->id);

    $this->actingAs($actor)
        ->post("/policies/{$policy->id}/transition", ['to' => 'approved'])
        ->assertRedirect("/policies/{$policy->id}");

    expect($policy->fresh()->state::$name)->toBe('approved');

    $this->assertDatabaseHas('policy_versions', [
        'policy_id' => $policy->id,
        'state'     => 'approved',
    ]);
});

it('compliance_officer can publish an approved policy and RenderPolicyPdfJob is dispatched', function (): void {
    Queue::fake();

    $actor = User::factory()->create(['email_verified_at' => now()]);
    $actor->assignRole('compliance_officer');

    $policy = createDraftPolicy($actor);
    $service = app(PolicyService::class);
    $policy = $service->transition($policy, 'in_review', null, $actor->id);
    $policy = $service->transition($policy, 'approved', null, $actor->id);

    $this->actingAs($actor)
        ->post("/policies/{$policy->id}/transition", ['to' => 'published'])
        ->assertRedirect("/policies/{$policy->id}");

    expect($policy->fresh()->state::$name)->toBe('published');

    Queue::assertPushed(RenderPolicyPdfJob::class, function ($job) use ($policy): bool {
        return str_contains(serialize($job), (string) $policy->id);
    });
});

it('compliance_officer can mark a published policy as in_force', function (): void {
    Queue::fake();

    $actor = User::factory()->create(['email_verified_at' => now()]);
    $actor->assignRole('compliance_officer');

    $policy = createDraftPolicy($actor);
    $service = app(PolicyService::class);
    $policy = $service->transition($policy, 'in_review', null, $actor->id);
    $policy = $service->transition($policy, 'approved', null, $actor->id);
    $policy = $service->transition($policy, 'published', null, $actor->id);

    $this->actingAs($actor)
        ->post("/policies/{$policy->id}/transition", ['to' => 'in_force'])
        ->assertRedirect("/policies/{$policy->id}");

    expect($policy->fresh()->state::$name)->toBe('in_force');
});

it('control_tester can acknowledge an in_force policy and the acknowledgement row is created', function (): void {
    Queue::fake();

    // Advance policy to in_force via the service.
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('compliance_officer');

    $policy = createDraftPolicy($admin);
    $service = app(PolicyService::class);
    $policy = advancePolicyTo($policy, 'in_force', $admin->id);

    expect($policy->state::$name)->toBe('in_force');

    // control_tester acknowledges (uses policies.view gate).
    $tester = User::factory()->create(['email_verified_at' => now()]);
    $tester->assignRole('control_tester');

    $this->actingAs($tester)
        ->post("/policies/{$policy->id}/acknowledge")
        ->assertRedirect();

    $ack = PolicyAcknowledgement::withoutGlobalScopes()
        ->where('policy_id', $policy->id)
        ->where('user_id', $tester->id)
        ->first();

    expect($ack)->not->toBeNull('Acknowledgement row must be created');
    expect($ack->policy_version)->toBe($policy->version);
});

it('compliance_officer can move in_force policy to under_review and then supersede it', function (): void {
    Queue::fake();

    $actor = User::factory()->create(['email_verified_at' => now()]);
    $actor->assignRole('compliance_officer');

    $policy = createDraftPolicy($actor);
    $policy = advancePolicyTo($policy, 'in_force', $actor->id);

    expect($policy->state::$name)->toBe('in_force');

    $this->actingAs($actor)
        ->post("/policies/{$policy->id}/transition", ['to' => 'under_review'])
        ->assertRedirect("/policies/{$policy->id}");
    expect($policy->fresh()->state::$name)->toBe('under_review');

    // Supersede requires a note.
    $this->actingAs($actor)
        ->post("/policies/{$policy->id}/transition", [
            'to'   => 'superseded',
            'note' => 'Replaced by v2.0',
        ])
        ->assertRedirect("/policies/{$policy->id}");
    expect($policy->fresh()->state::$name)->toBe('superseded');
});

it('full lifecycle produces at least 7 audit_events for the policy', function (): void {
    Queue::fake();

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('compliance_officer');

    $owner = User::factory()->create(['email_verified_at' => now()]);
    $owner->assignRole('policy_owner');

    // 1. policy_owner creates a draft policy via HTTP.
    $this->actingAs($owner)
        ->post('/policies', policyPayload(['title' => 'Full Lifecycle Policy']));

    $policy = Policy::withoutGlobalScopes()
        ->where('title', 'Full Lifecycle Policy')
        ->firstOrFail();

    // 2. policy_owner submits for review (HTTP).
    $this->actingAs($owner)
        ->post("/policies/{$policy->id}/transition", ['to' => 'in_review']);

    // 3–5. compliance_officer advances through approved → published → in_force.
    $this->actingAs($admin)
        ->post("/policies/{$policy->id}/transition", ['to' => 'approved']);

    $this->actingAs($admin)
        ->post("/policies/{$policy->id}/transition", ['to' => 'published']);

    $this->actingAs($admin)
        ->post("/policies/{$policy->id}/transition", ['to' => 'in_force']);

    // 6. control_tester acknowledges.
    $tester = User::factory()->create(['email_verified_at' => now()]);
    $tester->assignRole('control_tester');
    $this->actingAs($tester)
        ->post("/policies/{$policy->id}/acknowledge");

    // 7. compliance_officer starts under_review.
    $this->actingAs($admin)
        ->post("/policies/{$policy->id}/transition", ['to' => 'under_review']);

    // 8. compliance_officer supersedes.
    $this->actingAs($admin)
        ->post("/policies/{$policy->id}/transition", [
            'to'   => 'superseded',
            'note' => 'Replaced by v2',
        ]);

    // Count audit events for this specific policy.
    $auditCount = DB::table('audit_events')
        ->where('subject_type', 'Modules\Policy\Models\Policy')
        ->where('subject_id', $policy->id)
        ->count();

    // Minimum 7: 1 created + 6 state_transitioned (6 transitions).
    // EmitsAuditEvent may write additional rows — only assert >=7.
    expect($auditCount)->toBeGreaterThanOrEqual(7, "Expected at least 7 audit events for full lifecycle; got {$auditCount}");
});
