<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Controls\Models\Control;
use Modules\Policy\Models\Policy;
use Modules\Rcsa\Models\Risk;
use Modules\Rcsa\Models\RiskAssessmentCycle;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

// ─── Seeder idempotency ───────────────────────────────────────────────────────

it('running RolesAndPermissionsSeeder twice does not duplicate roles', function () {
    (new RolesAndPermissionsSeeder)->run(); // second run
    $roleCount = Role::count();
    // 6 roles defined in the matrix
    expect($roleCount)->toBe(6);
});

it('running RolesAndPermissionsSeeder twice does not duplicate permissions', function () {
    (new RolesAndPermissionsSeeder)->run(); // second run
    $permCount = Permission::count();
    // 44 permissions in the matrix (30 original + 7 Training M15 + 7 Incident M16 permissions)
    expect($permCount)->toBe(44);
});

// ─── super_admin bypass (Gate::before) ───────────────────────────────────────

it('super_admin can access every controller action', function () {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('super_admin');

    $policy = Policy::create([
        'title' => 'Admin Policy',
        'category' => 'aml',
        'owner_team' => 'Compliance',
    ]);

    // super_admin can view, create, update — no 403
    $this->actingAs($admin)->get('/policies')->assertOk();
    $this->actingAs($admin)->get("/policies/{$policy->id}")->assertOk();
    $this->actingAs($admin)->post('/policies', [
        'title' => 'New Policy',
        'category' => 'governance',
        'owner_team' => 'Legal',
    ])->assertRedirect();
});

// ─── Missing role → 403 ──────────────────────────────────────────────────────

it('user with no role gets 403 on protected controller actions', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    // no role assigned

    $this->actingAs($user)->get('/policies')->assertForbidden();
    $this->actingAs($user)->get('/controls')->assertForbidden();
    $this->actingAs($user)->get('/risk-assessments')->assertForbidden();
});

// ─── Role assignment idempotency ─────────────────────────────────────────────

it('assigning the same role twice does not create duplicate entries', function () {
    $user = User::factory()->create();
    $user->assignRole('auditor');
    $user->assignRole('auditor'); // second call

    expect($user->roles()->count())->toBe(1);
});

// ─── Panel access ────────────────────────────────────────────────────────────

it('compliance_officer can access admin panel', function () {
    $officer = User::factory()->create(['email_verified_at' => now()]);
    $officer->assignRole('compliance_officer');

    expect($officer->can('admin.access'))->toBeTrue();
});

it('risk_owner cannot access admin panel', function () {
    $riskOwner = User::factory()->create(['email_verified_at' => now()]);
    $riskOwner->assignRole('risk_owner');

    expect($riskOwner->can('admin.access'))->toBeFalse();
});

// ─── policy_owner separation of duties ───────────────────────────────────────

it('policy_owner can draft and submit for review but cannot approve', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $owner->assignRole('policy_owner');

    // Create a draft.
    $this->actingAs($owner)->post('/policies', [
        'title' => 'Owner Draft Policy',
        'category' => 'governance',
        'owner_team' => 'Legal',
    ])->assertRedirect();

    $policy = Policy::first();
    expect($policy->state::$name)->toBe('draft');

    // Submit for review.
    $this->actingAs($owner)->post("/policies/{$policy->id}/transition", [
        'to' => 'in_review',
    ])->assertRedirect("/policies/{$policy->id}");
    expect($policy->fresh()->state::$name)->toBe('in_review');

    // Cannot approve (separation of duties).
    $this->actingAs($owner)->post("/policies/{$policy->id}/transition", [
        'to' => 'approved',
    ])->assertForbidden();
    expect($policy->fresh()->state::$name)->toBe('in_review');
});

// ─── auditor read-only ────────────────────────────────────────────────────────

it('auditor can read all modules but write nothing', function () {
    $auditor = User::factory()->create(['email_verified_at' => now()]);
    $auditor->assignRole('auditor');

    $cycle = RiskAssessmentCycle::create([
        'name' => 'Audit Test Cycle',
        'lob' => 'IT',
        'cycle_year' => 2026,
        'cycle_quarter' => 2,
        'methodology' => '3x3',
    ]);

    $control = Control::create([
        'title' => 'Test Control',
        'control_type' => 'detective',
        'nature' => 'manual',
        'frequency' => 'monthly',
        'owner_team' => 'IT',
        'status' => 'active',
        'linked_obligation_ids' => [],
        'linked_risk_ids' => [],
    ]);

    // Read — all OK.
    $this->actingAs($auditor)->get('/risk-assessments')->assertOk();
    $this->actingAs($auditor)->get('/controls')->assertOk();
    $this->actingAs($auditor)->get('/policies')->assertOk();
    $this->actingAs($auditor)->get('/issues')->assertOk();

    // Write — all forbidden.
    $this->actingAs($auditor)->post('/risk-assessments', [
        'name' => 'Nope',
        'lob' => 'IT',
        'cycle_year' => 2026,
        'methodology' => '3x3',
    ])->assertForbidden();

    $this->actingAs($auditor)->post('/policies', [
        'title' => 'Nope',
        'category' => 'aml',
        'owner_team' => 'Audit',
    ])->assertForbidden();

    $this->actingAs($auditor)->put("/controls/{$control->id}", [
        'title' => 'Hacked',
        'control_type' => 'detective',
        'nature' => 'manual',
        'frequency' => 'monthly',
        'owner_team' => 'IT',
        'status' => 'active',
    ])->assertForbidden();
});

// ─── Inertia shared auth.permissions payload ─────────────────────────────────

it('shares permissions list in inertia auth payload for control_tester', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('control_tester');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.permissions', fn ($perms) => collect($perms)->contains('controls.view') &&
                collect($perms)->contains('issues.create') &&
                ! collect($perms)->contains('controls.create')
        )
        );
});

it('shares empty permissions array in inertia auth payload for roleless user', function () {
    // A user with no role gets an empty permissions array (not null, not missing).
    $user = User::factory()->create(['email_verified_at' => now()]);
    // No role assigned — getAllPermissions() returns empty collection.

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.permissions', fn ($perms) => is_array((array) $perms) && collect($perms)->isEmpty()
        )
        );
});

it('auditor receives policies.view and controls.view but not policies.create', function () {
    $auditor = User::factory()->create(['email_verified_at' => now()]);
    $auditor->assignRole('auditor');

    $this->actingAs($auditor)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.permissions', fn ($perms) => collect($perms)->contains('policies.view') &&
                collect($perms)->contains('controls.view') &&
                ! collect($perms)->contains('policies.create') &&
                ! collect($perms)->contains('controls.create')
        )
        );
});

// ─── risk_owner cycle-state guard ────────────────────────────────────────────

it('risk_owner can update risk in data_capture but not in in_review', function () {
    $riskOwner = User::factory()->create(['email_verified_at' => now()]);
    $riskOwner->assignRole('risk_owner');

    $cycle = RiskAssessmentCycle::create([
        'name' => 'Guard Test Cycle',
        'lob' => 'Operations',
        'cycle_year' => 2026,
        'cycle_quarter' => 3,
        'methodology' => '3x3',
    ]);

    $risk = Risk::create([
        'cycle_id' => $cycle->id,
        'title' => 'Operational Risk',
        'description' => 'Test.',
        'category' => 'operational',
        'linked_obligation_ids' => [],
    ]);

    // data_capture state — allowed.
    DB::table('risk_assessment_cycles')->where('id', $cycle->id)->update(['state' => 'data_capture']);

    $this->actingAs($riskOwner)->put("/risks/{$risk->id}", [
        'cycle_id' => $cycle->id,
        'title' => 'Updated in data_capture',
        'description' => 'Desc',
        'category' => 'operational',
    ])->assertRedirect();

    // in_review state — denied.
    DB::table('risk_assessment_cycles')->where('id', $cycle->id)->update(['state' => 'in_review']);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $this->actingAs($riskOwner)->put("/risks/{$risk->id}", [
        'cycle_id' => $cycle->id,
        'title' => 'Should be blocked',
        'description' => 'Desc',
        'category' => 'operational',
    ])->assertForbidden();
});
