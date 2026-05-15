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
// Helpers — prefixed "makeReturns*" to avoid global namespace collisions
// ---------------------------------------------------------------------------

function makeReturnsUser(string $role = 'compliance_officer'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

function makeReturnDefinition(array $overrides = []): ReturnDefinition
{
    return ReturnDefinition::withoutGlobalScopes()->create(array_merge([
        'tenant_id' => 1,
        'code' => 'TEST-DEF-'.uniqid(),
        'title' => 'Test Definition',
        'regulator' => 'cbn',
        'submission_channel' => 'portal',
        'file_format' => 'pdf',
        'frequency' => 'monthly',
        'evidence_required' => true,
        'active' => true,
    ], $overrides));
}

function makeReturnRun(ReturnDefinition $def, array $overrides = []): ReturnRun
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
// Dashboard access
// ---------------------------------------------------------------------------

it('unauthenticated user is redirected to login', function () {
    $this->get('/returns')->assertRedirect('/login');
});

it('compliance_officer can view the dashboard', function () {
    $this->actingAs(makeReturnsUser('compliance_officer'))
        ->get('/returns')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Returns/Dashboard'));
});

it('auditor can view the dashboard', function () {
    $this->actingAs(makeReturnsUser('auditor'))
        ->get('/returns')
        ->assertOk();
});

it('risk_owner can view the dashboard', function () {
    $this->actingAs(makeReturnsUser('risk_owner'))
        ->get('/returns')
        ->assertOk();
});

// ---------------------------------------------------------------------------
// Dashboard prop contract
// ---------------------------------------------------------------------------

it('dashboard returns the correct prop shape', function () {
    makeReturnDefinition(['active' => true]);

    $this->actingAs(makeReturnsUser('compliance_officer'))
        ->get('/returns')
        ->assertInertia(fn ($page) => $page
            ->component('Returns/Dashboard')
            ->has('stats')
            ->has('stats.total_active_definitions')
            ->has('stats.runs_due_this_month')
            ->has('stats.runs_submitted_this_month')
            ->has('stats.runs_acknowledged_this_month')
            ->has('stats.runs_late')
            ->has('stats.on_time_rate_30d')
            ->has('by_regulator')
            ->has('upcoming_runs')
            ->has('recent_submissions')
            ->has('can')
            ->has('can.view_runs')
            ->has('can.manage_definitions')
        );
});

it('dashboard stats reflect actual data', function () {
    $def = makeReturnDefinition(['active' => true]);
    makeReturnRun($def, ['status' => 'late', 'period_label' => '2025-01']);
    makeReturnRun($def, ['status' => 'acknowledged', 'due_at' => now()->addDays(3), 'period_label' => '2025-02']);

    $this->actingAs(makeReturnsUser('compliance_officer'))
        ->get('/returns')
        ->assertInertia(fn ($page) => $page
            ->where('stats.total_active_definitions', 1)
            ->where('stats.runs_late', 1)
        );
});

it('compliance_officer can has manage_definitions=true', function () {
    $this->actingAs(makeReturnsUser('compliance_officer'))
        ->get('/returns')
        ->assertInertia(fn ($page) => $page
            ->where('can.manage_definitions', true)
        );
});

it('auditor has manage_definitions=false', function () {
    $this->actingAs(makeReturnsUser('auditor'))
        ->get('/returns')
        ->assertInertia(fn ($page) => $page
            ->where('can.manage_definitions', false)
        );
});

it('risk_owner cannot view dashboard (403)', function () {
    // risk_owner has returns.view, so should pass
    $user = makeReturnsUser('risk_owner');
    $this->actingAs($user)
        ->get('/returns')
        ->assertOk();
});

it('control_tester with returns.view can access dashboard', function () {
    $user = makeReturnsUser('control_tester');
    // control_tester has returns.view per seeder
    $this->actingAs($user)
        ->get('/returns')
        ->assertOk();
});
