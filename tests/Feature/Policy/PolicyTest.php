<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Policy\Jobs\RenderPolicyPdfJob;
use Modules\Policy\Models\Policy;
use Modules\Policy\Models\PolicyAcknowledgement;
use Modules\Policy\Models\PolicyVersion;
use Modules\Policy\Services\PolicyService;
use Modules\Policy\States\Policy\Draft;
use Modules\Policy\States\Policy\InForce;
use Modules\Policy\States\Policy\InReview;

uses(RefreshDatabase::class);

function makeUser(): User
{
    return User::factory()->create();
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
    expect(\Illuminate\Support\Facades\DB::table('audit_events')->where('action', 'policy.created')->count())->toBe(1);
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

    \Illuminate\Support\Facades\DB::table('policies')
        ->where('id', $policy->id)
        ->update(['state' => 'published', 'effective_date' => now()->subDay()->toDateString()]);

    $this->artisan('policy:advance-states')->assertExitCode(0);

    $policy->refresh();
    expect(\Illuminate\Support\Facades\DB::table('policies')->where('id', $policy->id)->value('state'))->toBe('in_force');
});
