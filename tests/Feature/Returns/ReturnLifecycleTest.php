<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Returns\Models\ReturnDefinition;
use Modules\Returns\Models\ReturnRun;
use Modules\Returns\Services\ReturnsService;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

// ---------------------------------------------------------------------------
// Helpers — all prefixed to avoid collisions with other test files
// ---------------------------------------------------------------------------

function makeLifecycleUser(string $role = 'compliance_officer'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

function makeLifecycleDef(array $overrides = []): ReturnDefinition
{
    return ReturnDefinition::withoutGlobalScopes()->create(array_merge([
        'tenant_id' => 1,
        'code' => 'LIFECYCLE-'.uniqid(),
        'title' => 'Lifecycle Test Definition',
        'regulator' => 'cbn',
        'submission_channel' => 'portal',
        'file_format' => 'pdf',
        'frequency' => 'monthly',
        'evidence_required' => true,
        'active' => true,
    ], $overrides));
}

function makeLifecycleRun(ReturnDefinition $def, array $overrides = []): ReturnRun
{
    return ReturnRun::withoutGlobalScopes()->create(array_merge([
        'tenant_id' => 1,
        'return_definition_id' => $def->id,
        'period_label' => now()->format('Y-m'),
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
        'due_at' => now()->addDays(10),
        'status' => 'scheduled',
    ], $overrides));
}

// ---------------------------------------------------------------------------
// Happy path: full submit → approve → sign_off → acknowledge
// ---------------------------------------------------------------------------

it('maker can submit a run for review', function () {
    $def = makeLifecycleDef();
    $run = makeLifecycleRun($def);
    $maker = makeLifecycleUser('compliance_officer');

    $service = app(ReturnsService::class);
    $approval = $service->submitForReview($run, $maker->id, 'Prepared and ready.');

    $run->refresh();
    expect($run->status)->toBe('in_progress');
    expect($run->maker_id)->toBe($maker->id);
    expect($approval->step)->toBe('maker_submit');
    expect($approval->decision)->toBe('submitted');
});

it('checker approves the run (different user from maker)', function () {
    $def = makeLifecycleDef();
    $run = makeLifecycleRun($def);
    $maker = makeLifecycleUser('compliance_officer');
    $checker = makeLifecycleUser('compliance_officer');

    $service = app(ReturnsService::class);
    $service->submitForReview($run, $maker->id, null);
    $run->refresh();

    $approval = $service->approve($run, $checker->id, 'Reviewed OK.');
    $run->refresh();

    expect($run->checker_id)->toBe($checker->id);
    expect($approval->step)->toBe('checker_review');
    expect($approval->decision)->toBe('approved');
});

it('checker who is the same as maker gets a RuntimeException', function () {
    $def = makeLifecycleDef();
    $run = makeLifecycleRun($def);
    $maker = makeLifecycleUser('compliance_officer');

    $service = app(ReturnsService::class);
    $service->submitForReview($run, $maker->id, null);
    $run->refresh();

    expect(fn () => $service->approve($run, $maker->id, null))
        ->toThrow(RuntimeException::class, 'different user from the maker');
});

it('approver signs off and submission is recorded', function () {
    $def = makeLifecycleDef(['submission_channel' => 'manual']);
    $run = makeLifecycleRun($def);
    $maker = makeLifecycleUser('compliance_officer');
    $checker = makeLifecycleUser('compliance_officer');
    $approver = makeLifecycleUser('compliance_officer');

    $service = app(ReturnsService::class);
    $service->submitForReview($run, $maker->id, null);
    $run->refresh();
    $service->approve($run, $checker->id, null);
    $run->refresh();
    $result = $service->signOffAndSubmit($run, $approver->id, 'Approved.', 'REF-12345');
    $result->refresh();

    expect($result->status)->toBe('submitted_pending_ack');
    expect($result->submission_reference)->toBe('REF-12345');
    expect($result->submitted_at)->not->toBeNull();
    expect($result->approver_id)->toBe($approver->id);
});

it('approver who is maker gets a RuntimeException', function () {
    $def = makeLifecycleDef(['submission_channel' => 'manual']);
    $run = makeLifecycleRun($def);
    $maker = makeLifecycleUser('compliance_officer');
    $checker = makeLifecycleUser('compliance_officer');

    $service = app(ReturnsService::class);
    $service->submitForReview($run, $maker->id, null);
    $run->refresh();
    $service->approve($run, $checker->id, null);
    $run->refresh();

    expect(fn () => $service->signOffAndSubmit($run, $maker->id, null, null))
        ->toThrow(RuntimeException::class, 'different user from the maker');
});

it('approver who is checker gets a RuntimeException', function () {
    $def = makeLifecycleDef(['submission_channel' => 'manual']);
    $run = makeLifecycleRun($def);
    $maker = makeLifecycleUser('compliance_officer');
    $checker = makeLifecycleUser('compliance_officer');

    $service = app(ReturnsService::class);
    $service->submitForReview($run, $maker->id, null);
    $run->refresh();
    $service->approve($run, $checker->id, null);
    $run->refresh();

    expect(fn () => $service->signOffAndSubmit($run, $checker->id, null, null))
        ->toThrow(RuntimeException::class, 'different user from the checker');
});

it('acknowledgement is recorded after submission', function () {
    $def = makeLifecycleDef(['submission_channel' => 'manual']);
    $run = makeLifecycleRun($def);
    $maker = makeLifecycleUser('compliance_officer');
    $checker = makeLifecycleUser('compliance_officer');
    $approver = makeLifecycleUser('compliance_officer');
    $acker = makeLifecycleUser('compliance_officer');

    $service = app(ReturnsService::class);
    $service->submitForReview($run, $maker->id, null);
    $run->refresh();
    $service->approve($run, $checker->id, null);
    $run->refresh();
    $service->signOffAndSubmit($run, $approver->id, null, 'REF-99');
    $run->refresh();
    $result = $service->recordAcknowledgement($run, 'returns/ack.pdf', $acker->id);

    expect($result->status)->toBe('acknowledged');
    expect($result->acknowledgement_path)->toBe('returns/ack.pdf');
    expect($result->acknowledged_at)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// Approval records and audit trail
// ---------------------------------------------------------------------------

it('approval records are created at each step', function () {
    $def = makeLifecycleDef(['submission_channel' => 'manual']);
    $run = makeLifecycleRun($def);
    $maker = makeLifecycleUser('compliance_officer');
    $checker = makeLifecycleUser('compliance_officer');
    $approver = makeLifecycleUser('compliance_officer');

    $service = app(ReturnsService::class);
    $service->submitForReview($run, $maker->id, null);
    $run->refresh();
    $service->approve($run, $checker->id, null);
    $run->refresh();
    $service->signOffAndSubmit($run, $approver->id, null, 'REF-OK');

    $run->load('approvals');
    expect($run->approvals)->toHaveCount(3);
    $steps = $run->approvals->pluck('step')->toArray();
    expect($steps)->toContain('maker_submit');
    expect($steps)->toContain('checker_review');
    expect($steps)->toContain('approver_sign_off');
});

it('maker_submitted audit event is written', function () {
    $def = makeLifecycleDef();
    $run = makeLifecycleRun($def);
    $maker = makeLifecycleUser('compliance_officer');

    $service = app(ReturnsService::class);
    $service->submitForReview($run, $maker->id, null);

    $event = DB::table('audit_events')
        ->where('action', 'return_run.maker_submitted')
        ->where('subject_id', $run->id)
        ->first();

    expect($event)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// HTTP endpoints — separation of duties enforced via policy
// ---------------------------------------------------------------------------

it('POST submit returns 403 when user lacks returns.maker', function () {
    $def = makeLifecycleDef();
    $run = makeLifecycleRun($def);

    // control_tester does not have returns.maker
    $user = makeLifecycleUser('control_tester');

    $this->actingAs($user)
        ->post("/returns/runs/{$run->id}/submit", [])
        ->assertForbidden();
});

it('POST approve returns 403 when user is the maker', function () {
    $def = makeLifecycleDef();
    $run = makeLifecycleRun($def, ['status' => 'in_progress']);

    $maker = makeLifecycleUser('compliance_officer');
    $run->update(['maker_id' => $maker->id]);

    // compliance_officer has returns.checker, but is also the maker
    $this->actingAs($maker)
        ->post("/returns/runs/{$run->id}/approve", [])
        ->assertForbidden();
});

it('POST sign-off returns 403 when user is checker', function () {
    $def = makeLifecycleDef();
    $run = makeLifecycleRun($def, ['status' => 'in_progress']);

    $maker = makeLifecycleUser('compliance_officer');
    $checker = makeLifecycleUser('compliance_officer');
    $run->update(['maker_id' => $maker->id, 'checker_id' => $checker->id]);

    // checker cannot also be approver
    $this->actingAs($checker)
        ->post("/returns/runs/{$run->id}/sign-off", [])
        ->assertForbidden();
});
