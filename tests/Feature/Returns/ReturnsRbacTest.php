<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Returns\Models\ReturnDefinition;
use Modules\Returns\Models\ReturnRun;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

// ---------------------------------------------------------------------------
// Helpers — prefixed "makeRbac*" to avoid collisions
// ---------------------------------------------------------------------------

function makeRbacUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

function makeRbacDef(): ReturnDefinition
{
    return ReturnDefinition::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'code' => 'RBAC-'.uniqid(),
        'title' => 'RBAC Test Definition',
        'regulator' => 'cbn',
        'submission_channel' => 'portal',
        'file_format' => 'pdf',
        'frequency' => 'monthly',
        'evidence_required' => true,
        'active' => true,
    ]);
}

function makeRbacRun(ReturnDefinition $def, array $overrides = []): ReturnRun
{
    return ReturnRun::withoutGlobalScopes()->create(array_merge([
        'tenant_id' => 1,
        'return_definition_id' => $def->id,
        'period_label' => now()->format('Y-m'),
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
        'due_at' => now()->addDays(5),
        'status' => 'scheduled',
    ], $overrides));
}

// ---------------------------------------------------------------------------
// Dashboard RBAC
// ---------------------------------------------------------------------------

it('auditor can read the returns dashboard', function () {
    $this->actingAs(makeRbacUser('auditor'))
        ->get('/returns')
        ->assertOk();
});

it('risk_owner can read the returns dashboard', function () {
    $this->actingAs(makeRbacUser('risk_owner'))
        ->get('/returns')
        ->assertOk();
});

it('policy_owner can read the returns dashboard', function () {
    $this->actingAs(makeRbacUser('policy_owner'))
        ->get('/returns')
        ->assertOk();
});

it('control_tester can read the returns dashboard', function () {
    $this->actingAs(makeRbacUser('control_tester'))
        ->get('/returns')
        ->assertOk();
});

// ---------------------------------------------------------------------------
// Runs index RBAC
// ---------------------------------------------------------------------------

it('auditor can view runs index', function () {
    $this->actingAs(makeRbacUser('auditor'))
        ->get('/returns/runs')
        ->assertOk();
});

it('risk_owner can view runs index', function () {
    $this->actingAs(makeRbacUser('risk_owner'))
        ->get('/returns/runs')
        ->assertOk();
});

// ---------------------------------------------------------------------------
// Submit (maker step)
// ---------------------------------------------------------------------------

it('auditor cannot submit a run for review', function () {
    $def = makeRbacDef();
    $run = makeRbacRun($def);

    $this->actingAs(makeRbacUser('auditor'))
        ->post("/returns/runs/{$run->id}/submit", [])
        ->assertForbidden();
});

it('risk_owner cannot submit a run for review', function () {
    $def = makeRbacDef();
    $run = makeRbacRun($def);

    $this->actingAs(makeRbacUser('risk_owner'))
        ->post("/returns/runs/{$run->id}/submit", [])
        ->assertForbidden();
});

it('compliance_officer can submit a run for review', function () {
    $def = makeRbacDef();
    $run = makeRbacRun($def);

    $this->actingAs(makeRbacUser('compliance_officer'))
        ->post("/returns/runs/{$run->id}/submit", ['notes' => 'ready'])
        ->assertRedirect();
});

// ---------------------------------------------------------------------------
// Approve (checker step)
// ---------------------------------------------------------------------------

it('risk_owner cannot approve as checker', function () {
    $def = makeRbacDef();
    $makerUser = makeRbacUser('compliance_officer');
    $run = makeRbacRun($def, ['status' => 'in_progress', 'maker_id' => $makerUser->id]);

    $this->actingAs(makeRbacUser('risk_owner'))
        ->post("/returns/runs/{$run->id}/approve", [])
        ->assertForbidden();
});

it('compliance_officer (different from maker) can approve as checker', function () {
    $def = makeRbacDef();
    $maker = makeRbacUser('compliance_officer');
    $checker = makeRbacUser('compliance_officer');
    $run = makeRbacRun($def, ['status' => 'in_progress', 'maker_id' => $maker->id]);

    $this->actingAs($checker)
        ->post("/returns/runs/{$run->id}/approve", [])
        ->assertRedirect();
});

// ---------------------------------------------------------------------------
// Sign-off (approver step)
// ---------------------------------------------------------------------------

it('control_tester cannot sign off', function () {
    $def = makeRbacDef();
    $maker = makeRbacUser('compliance_officer');
    $checker = makeRbacUser('compliance_officer');
    $run = makeRbacRun($def, [
        'status' => 'in_progress',
        'maker_id' => $maker->id,
        'checker_id' => $checker->id,
    ]);

    $this->actingAs(makeRbacUser('control_tester'))
        ->post("/returns/runs/{$run->id}/sign-off", [])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Acknowledge
// ---------------------------------------------------------------------------

it('auditor cannot record acknowledgement', function () {
    $def = makeRbacDef();
    $run = makeRbacRun($def, ['status' => 'submitted_pending_ack']);

    $this->actingAs(makeRbacUser('auditor'))
        ->post("/returns/runs/{$run->id}/acknowledge", ['acknowledgement_path' => 'ack.pdf'])
        ->assertForbidden();
});

it('compliance_officer can record acknowledgement', function () {
    $def = makeRbacDef();
    $run = makeRbacRun($def, ['status' => 'submitted_pending_ack']);

    $this->actingAs(makeRbacUser('compliance_officer'))
        ->post("/returns/runs/{$run->id}/acknowledge", ['acknowledgement_path' => 'returns/ack.pdf'])
        ->assertRedirect();
});

// ---------------------------------------------------------------------------
// Show page
// ---------------------------------------------------------------------------

it('auditor can view a run show page', function () {
    $def = makeRbacDef();
    $run = makeRbacRun($def);

    $this->actingAs(makeRbacUser('auditor'))
        ->get("/returns/runs/{$run->id}")
        ->assertOk();
});

it('unauthenticated user is redirected from runs show', function () {
    $def = makeRbacDef();
    $run = makeRbacRun($def);

    $this->get("/returns/runs/{$run->id}")->assertRedirect('/login');
});
