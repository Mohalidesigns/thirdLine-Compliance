<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Modules\Policy\Jobs\RenderPolicyPdfJob;
use Modules\Policy\Models\Policy;
use Modules\Policy\Models\PolicyVersion;
use Modules\Policy\Services\PolicyService;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

/**
 * Returns a compliance_officer user who can perform all policy actions.
 */
function makeUser(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('compliance_officer');

    return $user;
}

function makePolicy(array $overrides = []): Policy
{
    return Policy::create(array_merge([
        'title' => 'Test AML Policy',
        'category' => 'aml',
        'owner_team' => 'Compliance',
        'summary' => 'A test policy',
        'body' => 'Policy body content',
    ], $overrides));
}

it('creates a draft policy and emits one audit event', function () {
    $user = makeUser();

    $this->actingAs($user)->post('/policies', [
        'title' => 'New AML Policy',
        'category' => 'aml',
        'owner_team' => 'Compliance',
        'summary' => 'Summary text',
    ])->assertRedirect();

    $this->assertDatabaseHas('policies', [
        'title' => 'New AML Policy',
        'state' => 'draft',
    ]);

    $this->assertDatabaseHas('audit_events', ['action' => 'policy.created']);
    expect(DB::table('audit_events')->where('action', 'policy.created')->count())->toBe(1);
});

it('transitions draft to in_review', function () {
    $user = makeUser();
    $policy = makePolicy();

    $this->actingAs($user)->post("/policies/{$policy->id}/transition", [
        'to' => 'in_review',
    ])->assertRedirect("/policies/{$policy->id}");

    $policy->refresh();
    expect($policy->state::$name)->toBe('in_review');

    $this->assertDatabaseHas('policy_versions', [
        'policy_id' => $policy->id,
        'state' => 'in_review',
    ]);
});

it('blocks invalid transitions', function () {
    $user = makeUser();
    $policy = makePolicy();

    $this->actingAs($user)->post("/policies/{$policy->id}/transition", [
        'to' => 'published',
    ])->assertRedirect();

    $policy->refresh();
    expect($policy->state::$name)->toBe('draft');
});

it('approves and publish triggers PDF render job', function () {
    Queue::fake();

    $user = makeUser();
    $policy = makePolicy();
    $service = app(PolicyService::class);

    $service->transition($policy, 'in_review', null, $user->id);
    $service->transition($policy, 'approved', null, $user->id);

    Queue::assertNothingPushed();

    $service->transition($policy, 'published', null, $user->id);

    Queue::assertPushed(RenderPolicyPdfJob::class, fn ($job) => true);
});

it('blocks edit when state is not draft', function () {
    $user = makeUser();
    $policy = makePolicy();
    $service = app(PolicyService::class);
    $service->transition($policy, 'in_review', null, $user->id);

    $this->actingAs($user)->put("/policies/{$policy->id}", [
        'title' => 'Updated Title',
        'category' => 'aml',
        'owner_team' => 'Compliance',
    ])->assertStatus(403);
});

it('records a version row on every transition', function () {
    $user = makeUser();
    $policy = makePolicy();
    $service = app(PolicyService::class);

    expect(PolicyVersion::where('policy_id', $policy->id)->count())->toBe(0);

    $service->transition($policy, 'in_review', null, $user->id);
    expect(PolicyVersion::where('policy_id', $policy->id)->count())->toBe(1);

    $service->transition($policy, 'approved', null, $user->id);
    expect(PolicyVersion::where('policy_id', $policy->id)->count())->toBe(2);

    $service->transition($policy, 'published', null, $user->id);
    expect(PolicyVersion::where('policy_id', $policy->id)->count())->toBe(3);
});

it('user can acknowledge a published policy', function () {
    $user = makeUser();
    $policy = makePolicy();
    $service = app(PolicyService::class);

    $service->transition($policy, 'in_review', null, $user->id);
    $service->transition($policy, 'approved', null, $user->id);
    $service->transition($policy, 'published', null, $user->id);

    $this->actingAs($user)->post("/policies/{$policy->id}/acknowledge")
        ->assertRedirect();

    $this->assertDatabaseHas('policy_acknowledgements', [
        'policy_id' => $policy->id,
        'user_id' => $user->id,
        'policy_version' => $policy->version,
    ]);
});

it('cannot acknowledge twice for the same version', function () {
    $user = makeUser();
    $policy = makePolicy();
    $service = app(PolicyService::class);

    $service->transition($policy, 'in_review', null, $user->id);
    $service->transition($policy, 'approved', null, $user->id);
    $service->transition($policy, 'published', null, $user->id);

    $service->acknowledge($policy, $user->id);

    $this->actingAs($user)->post("/policies/{$policy->id}/acknowledge")
        ->assertSessionHasErrors('acknowledgement');
});

it('scheduled command advances eligible policies', function () {
    $user = makeUser();
    $service = app(PolicyService::class);

    $policy = makePolicy(['effective_date' => now()->subDay()->toDateString()]);
    $service->transition($policy, 'in_review', null, $user->id);
    $service->transition($policy, 'approved', null, $user->id);
    $policy->refresh();

    DB::table('policies')
        ->where('id', $policy->id)
        ->update(['state' => 'published', 'effective_date' => now()->subDay()->toDateString()]);

    $this->artisan('policy:advance-states')->assertExitCode(0);

    $policy->refresh();
    expect(DB::table('policies')->where('id', $policy->id)->value('state'))->toBe('in_force');
});

// ─── RBAC: wrong-role gets 403 ────────────────────────────────────────────────

it('unauthenticated request to policies.store gets 302 redirect', function () {
    $this->post('/policies', [
        'title' => 'Should fail',
        'category' => 'aml',
        'owner_team' => 'Compliance',
    ])->assertRedirect();
});

it('policy_owner can create a draft policy', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $owner->assignRole('policy_owner');

    $this->actingAs($owner)->post('/policies', [
        'title' => 'My Policy',
        'category' => 'governance',
        'owner_team' => 'Legal',
    ])->assertRedirect();

    $this->assertDatabaseHas('policies', ['title' => 'My Policy', 'state' => 'draft']);
});

it('policy_owner can submit a draft for review', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $owner->assignRole('policy_owner');
    $policy = makePolicy();

    $this->actingAs($owner)->post("/policies/{$policy->id}/transition", [
        'to' => 'in_review',
    ])->assertRedirect("/policies/{$policy->id}");

    expect($policy->fresh()->state::$name)->toBe('in_review');
});

it('policy_owner cannot approve a policy in review', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $owner->assignRole('policy_owner');

    $policy = makePolicy();
    $service = app(PolicyService::class);
    $service->transition($policy, 'in_review', null, $owner->id);

    $this->actingAs($owner)->post("/policies/{$policy->id}/transition", [
        'to' => 'approved',
    ])->assertForbidden();

    expect($policy->fresh()->state::$name)->toBe('in_review');
});

it('risk_owner cannot create a policy', function () {
    $riskOwner = User::factory()->create(['email_verified_at' => now()]);
    $riskOwner->assignRole('risk_owner');

    $this->actingAs($riskOwner)->post('/policies', [
        'title' => 'Unauthorized Policy',
        'category' => 'aml',
        'owner_team' => 'Risk',
    ])->assertForbidden();
});

it('auditor can view policies but cannot create one', function () {
    $auditor = User::factory()->create(['email_verified_at' => now()]);
    $auditor->assignRole('auditor');
    makePolicy();

    $this->actingAs($auditor)->get('/policies')->assertOk();
    $this->actingAs($auditor)->post('/policies', [
        'title' => 'Should 403',
        'category' => 'aml',
        'owner_team' => 'Audit',
    ])->assertForbidden();
});

// ─── Task 4: created_by column + PolicyPolicy enforcement ─────────────────────

it('policy_owner can edit their own draft policy', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $owner->assignRole('policy_owner');

    // Create via the store route so created_by is stamped.
    $this->actingAs($owner)->post('/policies', [
        'title' => 'My Own Draft',
        'category' => 'governance',
        'owner_team' => 'Legal',
    ])->assertRedirect();

    $policy = Policy::where('title', 'My Own Draft')->firstOrFail();
    expect($policy->created_by)->toBe($owner->id);

    $this->actingAs($owner)
        ->put("/policies/{$policy->id}", [
            'title' => 'My Own Draft Updated',
            'category' => 'governance',
            'owner_team' => 'Legal',
        ])
        ->assertRedirect();

    expect($policy->fresh()->title)->toBe('My Own Draft Updated');
});

it('policy_owner cannot edit someone elses draft policy', function () {
    $owner1 = User::factory()->create(['email_verified_at' => now()]);
    $owner1->assignRole('policy_owner');

    $owner2 = User::factory()->create(['email_verified_at' => now()]);
    $owner2->assignRole('policy_owner');

    // owner1 creates the policy.
    $this->actingAs($owner1)->post('/policies', [
        'title' => 'Owner1 Policy',
        'category' => 'governance',
        'owner_team' => 'Legal',
    ])->assertRedirect();

    $policy = Policy::where('title', 'Owner1 Policy')->firstOrFail();
    expect($policy->created_by)->toBe($owner1->id);

    // owner2 tries to edit it — must be forbidden.
    $this->actingAs($owner2)
        ->put("/policies/{$policy->id}", [
            'title' => 'Hijacked Title',
            'category' => 'governance',
            'owner_team' => 'Legal',
        ])
        ->assertForbidden();
});

it('policy_owner cannot edit their own policy once it is in_review', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $owner->assignRole('policy_owner');

    $this->actingAs($owner)->post('/policies', [
        'title' => 'Going for Review',
        'category' => 'governance',
        'owner_team' => 'Legal',
    ])->assertRedirect();

    $policy = Policy::where('title', 'Going for Review')->firstOrFail();

    // Submit for review.
    $this->actingAs($owner)->post("/policies/{$policy->id}/transition", [
        'to' => 'in_review',
    ])->assertRedirect();

    expect($policy->fresh()->state::$name)->toBe('in_review');

    // Attempt to edit — must be 403.
    $this->actingAs($owner)
        ->put("/policies/{$policy->id}", [
            'title' => 'Sneaky update',
            'category' => 'governance',
            'owner_team' => 'Legal',
        ])
        ->assertForbidden();
});
