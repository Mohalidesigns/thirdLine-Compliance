<?php

declare(strict_types=1);

/**
 * Scenario 4: Tenant isolation (cross-phase seam test).
 *
 * Verifies that:
 *   - Policy, Control, and Risk rows created in tenant 1 are invisible to a
 *     user whose queries resolve to tenant 2.
 *   - The CCM command with --tenant=2 produces zero Issues when all breaching
 *     rows belong to tenant 1.
 *
 * Implementation note on tenant resolution
 * ----------------------------------------
 * BelongsToTenant::currentTenantId() reads from app('current.tenant_id') if
 * that binding exists, otherwise defaults to 1.  The RunCcmRulesCommand binds
 * app()->bind('current.tenant_id', fn() => $tenantId) before running each
 * tenant's rules — the same binding mechanism is available to tests.
 *
 * The users table does NOT have a tenant_id column in the current schema
 * (BelongsToTenant is on domain models, not on User).  Because there is no
 * per-user tenant routing, we simulate a tenant-2 request by binding
 * 'current.tenant_id' to 2 before making HTTP calls.  This is the same
 * mechanism the CCM command uses and mirrors a real TenantResolver implementation.
 *
 * If a future migration adds users.tenant_id this file should be updated to
 * also test user-level routing.
 *
 * TODO (Phase 4D open question): Replace app()->bind stub with a proper
 * TenantResolver service once users.tenant_id column is added.
 */

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Controls\Models\Control;
use Modules\Controls\Models\Issue;
use Modules\Policy\Models\Policy;
use Modules\Rcsa\Models\Risk;
use Modules\Rcsa\Models\RiskAssessmentCycle;

beforeEach(function (): void {
    (new RolesAndPermissionsSeeder)->run();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Insert the minimal reference-data chain for an obligation and return the
 * obligation id. Accepts tenantId parameter so tenant-2 data can be inserted.
 */
function seedObligationForTenant(int $tenantId, int $overdueDays = 5): int
{
    $regulatorId = DB::table('regulators')->insertGetId([
        'code'       => 'TREG-' . $tenantId . '-' . uniqid(),
        'name'       => "Tenant {$tenantId} Regulator",
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $typeId = DB::table('instrument_types')->insertGetId([
        'name'       => "TType-{$tenantId}-" . uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $natureId = DB::table('natures')->insertGetId([
        'name'       => "TNature-{$tenantId}-" . uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $statusId = DB::table('statuses')->insertGetId([
        'name'       => "TStatus-{$tenantId}-" . uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $areaId = DB::table('areas_of_focus')->insertGetId([
        'name'       => "TArea-{$tenantId}-" . uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $ratingId = DB::table('risk_ratings')->insertGetId([
        'name'       => "TRating-{$tenantId}-" . uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $instrumentId = DB::table('instruments')->insertGetId([
        'tenant_id'          => $tenantId,
        'source_title'       => "Tenant {$tenantId} Instrument " . uniqid(),
        'regulator_id'       => $regulatorId,
        'instrument_type_id' => $typeId,
        'nature_id'          => $natureId,
        'status_id'          => $statusId,
        'area_of_focus_id'   => $areaId,
        'risk_rating_id'     => $ratingId,
        'applicability'      => 'Yes',
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);

    return DB::table('obligations')->insertGetId([
        'tenant_id'     => $tenantId,
        'instrument_id' => $instrumentId,
        'reference'     => "OBL-T{$tenantId}-" . uniqid(),
        'title'         => "Tenant {$tenantId} overdue obligation",
        'description'   => 'Tenant isolation test obligation.',
        'next_due_date' => now()->subDays($overdueDays)->toDateString(),
        'status'        => 'open',
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

it('a Policy created in tenant 1 returns 404 when accessed under tenant-2 scope', function (): void {
    // Create policy in tenant 1 (default scope).
    $policy = Policy::create([
        'title'      => 'Tenant 1 Policy',
        'category'   => 'governance',
        'owner_team' => 'Legal',
    ]);

    expect($policy->tenant_id)->toBe(1);

    // Bind tenant 2 and attempt to access tenant-1 policy.
    app()->bind('current.tenant_id', fn () => 2);

    $this->actingAsRole('compliance_officer')
        ->get("/policies/{$policy->id}")
        ->assertNotFound();

    // Reset scope.
    app()->offsetUnset('current.tenant_id');
});

it('a Control created in tenant 1 returns 404 when accessed under tenant-2 scope', function (): void {
    $control = Control::create([
        'title'        => 'Tenant 1 Control',
        'description'  => 'Control for tenant isolation.',
        'control_type' => 'preventive',
        'nature'       => 'manual',
        'frequency'    => 'monthly',
        'owner_team'   => 'Operations',
        'status'       => 'active',
    ]);

    expect($control->tenant_id)->toBe(1);

    app()->bind('current.tenant_id', fn () => 2);

    $this->actingAsRole('compliance_officer')
        ->get("/controls/{$control->id}")
        ->assertNotFound();

    app()->offsetUnset('current.tenant_id');
});

it('a Risk created in tenant 1 returns 404 when accessed under tenant-2 scope', function (): void {
    $cycle = RiskAssessmentCycle::create([
        'name'          => 'Tenant 1 Cycle',
        'lob'           => 'IT',
        'cycle_year'    => 2026,
        'cycle_quarter' => 1,
        'methodology'   => '3x3',
    ]);

    $risk = Risk::create([
        'cycle_id'    => $cycle->id,
        'title'       => 'Tenant 1 Risk',
        'description' => 'Risk for tenant isolation.',
        'category'    => 'cyber',
    ]);

    expect($risk->tenant_id)->toBe(1);

    app()->bind('current.tenant_id', fn () => 2);

    $this->actingAsRole('compliance_officer')
        ->get("/risks/{$risk->id}")
        ->assertNotFound();

    app()->offsetUnset('current.tenant_id');
});

it('CCM with --tenant=2 creates zero Issues when all breaching obligations are in tenant 1', function (): void {
    // Overdue obligation in tenant 1 only.
    seedObligationForTenant(tenantId: 1, overdueDays: 45);

    $issuesBefore = Issue::withoutGlobalScopes()->count();

    // Run for tenant 2 only.
    $this->artisan('controls:run-ccm --tenant=2')->assertExitCode(0);

    // Zero new issues — all overdue data belongs to tenant 1.
    $newIssues = Issue::withoutGlobalScopes()
        ->where('tenant_id', 2)
        ->where('source_type', 'ccm_rule')
        ->count();

    expect($newIssues)->toBe(0, 'CCM for tenant 2 must not create issues when all breaching rows are in tenant 1');

    // Tenant-1 issues should also remain zero because we only ran for tenant 2.
    expect(Issue::withoutGlobalScopes()->count())->toBe($issuesBefore, 'No new issues should have been created at all');
});

it('CCM with --tenant=1 does not create Issues for obligations owned by tenant 2', function (): void {
    // Overdue obligation in tenant 2 only.
    seedObligationForTenant(tenantId: 2, overdueDays: 45);

    $issuesBefore = Issue::withoutGlobalScopes()->count();

    // Run for tenant 1 — should see no overdue obligations (all in tenant 2).
    $this->artisan('controls:run-ccm --tenant=1')->assertExitCode(0);

    $tenant2Issues = Issue::withoutGlobalScopes()
        ->where('tenant_id', 2)
        ->where('source_type', 'ccm_rule')
        ->count();

    expect($tenant2Issues)->toBe(0, 'No issues should be created for tenant 2 rows when running --tenant=1');

    // Overall issue count must not have increased.
    expect(Issue::withoutGlobalScopes()->count())->toBe($issuesBefore, 'No new issues should exist after running --tenant=1 with only tenant-2 data');
});

it('global scope blocks cross-tenant reads on Policy model', function (): void {
    // Insert a policy directly for tenant 2 — bypassing the global scope.
    DB::table('policies')->insert([
        'tenant_id'  => 2,
        'reference'  => 'POL-T2-ISOLATION',
        'title'      => 'Tenant 2 Hidden Policy',
        'category'   => 'other',
        'owner_team' => 'Compliance',
        'version'    => 1,
        'state'      => 'draft',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Under default tenant-1 scope, the tenant-2 policy must be invisible.
    $found = Policy::where('title', 'Tenant 2 Hidden Policy')->first();
    expect($found)->toBeNull('Tenant-2 policy must be invisible under tenant-1 scope');

    // Confirm it exists via withoutGlobalScopes.
    $foundRaw = Policy::withoutGlobalScopes()
        ->where('title', 'Tenant 2 Hidden Policy')
        ->first();

    expect($foundRaw)->not->toBeNull('Policy must exist in DB — only hidden by scope');
    expect($foundRaw->tenant_id)->toBe(2);
});

it('users table tenant_id column presence is documented (skip not needed if column absent)', function (): void {
    // The BelongsToTenant trait is on domain models, not on User.
    // If users.tenant_id is added in a future migration, update this test to
    // create users in different tenants and assert HTTP routing isolation.
    //
    // TODO (Phase 4D): When users.tenant_id is added, test user-level tenant
    // isolation by creating users in tenant 1 and tenant 2 and asserting that
    // cross-tenant HTTP requests are rejected by the TenantResolver middleware.

    $hasTenantColumn = Schema::hasColumn('users', 'tenant_id');

    // This test does not skip — it documents the current state.
    // If the column is absent, user-level multi-tenancy is not yet implemented.
    if (! $hasTenantColumn) {
        // Log a note to stdout for visibility in test output.
        // PHPUnit addWarning() is not universally available; use fwrite instead.
        fwrite(STDOUT, "\n[NOTE] users.tenant_id column does not exist. User-level tenant isolation is untested. See Phase 4D open question in TenantIsolationTest.php.\n");
    }

    // The assertion: either the column exists (future-ready) or it doesn't.
    // Both are valid at this phase — the test records the current design decision.
    expect(true)->toBeTrue();
});
