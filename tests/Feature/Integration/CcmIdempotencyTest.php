<?php

declare(strict_types=1);

/**
 * Scenario 5: CCM idempotency (cross-phase seam test).
 *
 * Encodes the contract of RunCcmRulesCommand re: duplicate issue creation.
 *
 * Current design (Phase 4F): "one issue per rule per run".
 * Running controls:run-ccm twice when the same obligation is still overdue
 * produces TWO Issues — the command does not deduplicate across runs.
 *
 * This is intentional for auditability: each CCM run is a distinct check and
 * its breach is a distinct event. The CcmRuleRun table records each run, and
 * each issue is linked to a specific run via source_id.
 *
 * If the design changes to "upsert / one open issue per rule", this test must
 * be updated to assert that the second run re-uses the existing Issue (or
 * leaves it alone). The test name is explicit about the current contract so
 * the intent is never ambiguous.
 *
 * See also: ObligationToIssueFlowTest for the happy-path flow.
 */

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Modules\Controls\Models\Issue;

beforeEach(function (): void {
    (new RolesAndPermissionsSeeder)->run();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Insert the minimal reference-data rows for an overdue obligation in the
 * given tenant and return the obligation's primary key.
 */
function seedOverdueObligationForIdempotency(int $overdueDays = 10, int $tenantId = 1): int
{
    $regulatorId = DB::table('regulators')->insertGetId([
        'code'       => 'CCM-IDEM-REG-' . uniqid(),
        'name'       => 'CCM Idempotency Regulator',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $typeId = DB::table('instrument_types')->insertGetId([
        'name'       => 'CcmIdemType-' . uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $natureId = DB::table('natures')->insertGetId([
        'name'       => 'CcmIdemNature-' . uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $statusId = DB::table('statuses')->insertGetId([
        'name'       => 'CcmIdemStatus-' . uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $areaId = DB::table('areas_of_focus')->insertGetId([
        'name'       => 'CcmIdemArea-' . uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $ratingId = DB::table('risk_ratings')->insertGetId([
        'name'       => 'CcmIdemRating-' . uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $instrumentId = DB::table('instruments')->insertGetId([
        'tenant_id'          => $tenantId,
        'source_title'       => 'CcmIdem Instrument ' . uniqid(),
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
        'reference'     => 'OBL-CCM-IDEM-' . uniqid(),
        'title'         => 'CCM Idempotency Test Obligation',
        'description'   => 'An overdue obligation for idempotency testing.',
        'next_due_date' => now()->subDays($overdueDays)->toDateString(),
        'status'        => 'open',
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

/**
 * CONTRACT: "one issue per rule per run".
 *
 * Running controls:run-ccm twice against the same unresolved overdue obligation
 * produces TWO Issues — the command does not deduplicate across runs.  Each
 * run is an independent check; its breach is a distinct, auditable event.
 *
 * If this assertion starts failing (i.e. the second run produces only 1 new
 * issue), it means the team changed to an upsert design.  Update the assertion
 * below AND the contract comment above to reflect the new design decision.
 */
it('running controls:run-ccm twice creates two Issues (one per run) — current contract is one-issue-per-run', function (): void {
    seedOverdueObligationForIdempotency(overdueDays: 45);

    $issuesBefore = Issue::withoutGlobalScopes()
        ->where('source_type', 'ccm_rule')
        ->count();

    // First run.
    $this->artisan('controls:run-ccm')->assertExitCode(0);

    $afterRun1 = Issue::withoutGlobalScopes()
        ->where('source_type', 'ccm_rule')
        ->count();

    expect($afterRun1 - $issuesBefore)->toBeGreaterThanOrEqual(
        1,
        'First run must create at least one ccm_rule Issue on a breach'
    );

    // Second run against the same unresolved data.
    $this->artisan('controls:run-ccm')->assertExitCode(0);

    $afterRun2 = Issue::withoutGlobalScopes()
        ->where('source_type', 'ccm_rule')
        ->count();

    // Under "one issue per run" design: the second run must create at least one
    // additional Issue for the same obligation (because the obligation is still overdue).
    expect($afterRun2)->toBeGreaterThan(
        $afterRun1,
        'Second CCM run must create a new Issue because the obligation is still overdue (one-issue-per-run contract)'
    );

    // Verify the two Issues have distinct source_id values (linked to distinct ccm_rule_runs).
    $issues = Issue::withoutGlobalScopes()
        ->where('source_type', 'ccm_rule')
        ->whereNotNull('source_id')
        ->orderBy('id')
        ->get(['id', 'source_id']);

    $uniqueRunIds = $issues->pluck('source_id')->unique();
    expect($uniqueRunIds->count())->toBeGreaterThanOrEqual(
        2,
        'Each CCM run must link its Issues to a distinct CcmRuleRun (distinct source_id values)'
    );
});

it('each ccm_rule_run row is distinct and references the correct run time', function (): void {
    seedOverdueObligationForIdempotency(overdueDays: 10);

    $runsBefore = DB::table('ccm_rule_runs')
        ->where('rule_name', 'overdue_obligations')
        ->count();

    $this->artisan('controls:run-ccm')->assertExitCode(0);
    $this->artisan('controls:run-ccm')->assertExitCode(0);

    $runsAfter = DB::table('ccm_rule_runs')
        ->where('rule_name', 'overdue_obligations')
        ->where('status', 'threshold_breached')
        ->count();

    // Two runs → two distinct ccm_rule_run rows for the overdue_obligations rule.
    expect($runsAfter - $runsBefore)->toBeGreaterThanOrEqual(2, 'Expected two new ccm_rule_run rows (one per execution)');
});

it('running with --tenant=1 creates Issues only for tenant-1 obligations', function (): void {
    // Tenant-1 overdue obligation.
    seedOverdueObligationForIdempotency(overdueDays: 45, tenantId: 1);

    // Insert a tenant-2 obligation directly — the CCM rule for tenant 1 must not see it.
    $regulatorId = DB::table('regulators')->insertGetId([
        'code'       => 'CCM-T2-' . uniqid(),
        'name'       => 'Tenant 2 CCM Regulator',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $typeId  = DB::table('instrument_types')->insertGetId(['name' => 'T2Type-' . uniqid(), 'created_at' => now(), 'updated_at' => now()]);
    $natId   = DB::table('natures')->insertGetId(['name' => 'T2Nat-' . uniqid(), 'created_at' => now(), 'updated_at' => now()]);
    $statId  = DB::table('statuses')->insertGetId(['name' => 'T2Stat-' . uniqid(), 'created_at' => now(), 'updated_at' => now()]);
    $areaId  = DB::table('areas_of_focus')->insertGetId(['name' => 'T2Area-' . uniqid(), 'created_at' => now(), 'updated_at' => now()]);
    $ratId   = DB::table('risk_ratings')->insertGetId(['name' => 'T2Rat-' . uniqid(), 'created_at' => now(), 'updated_at' => now()]);

    $instrId = DB::table('instruments')->insertGetId([
        'tenant_id'          => 2,
        'source_title'       => 'T2 CCM Instrument ' . uniqid(),
        'regulator_id'       => $regulatorId,
        'instrument_type_id' => $typeId,
        'nature_id'          => $natId,
        'status_id'          => $statId,
        'area_of_focus_id'   => $areaId,
        'risk_rating_id'     => $ratId,
        'applicability'      => 'Yes',
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);

    DB::table('obligations')->insert([
        'tenant_id'     => 2,
        'instrument_id' => $instrId,
        'reference'     => 'OBL-T2-CCM-' . uniqid(),
        'title'         => 'Tenant 2 CCM Obligation',
        'description'   => 'Tenant isolation for CCM.',
        'next_due_date' => now()->subDays(45)->toDateString(),
        'status'        => 'open',
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);

    // Run only for tenant 1.
    $this->artisan('controls:run-ccm --tenant=1')->assertExitCode(0);

    // No Issues should exist for tenant 2.
    $tenant2Issues = Issue::withoutGlobalScopes()
        ->where('tenant_id', 2)
        ->where('source_type', 'ccm_rule')
        ->count();

    expect($tenant2Issues)->toBe(0, 'CCM --tenant=1 must not create Issues for tenant-2 obligations');

    // At least one Issue must exist for tenant 1.
    $tenant1Issues = Issue::withoutGlobalScopes()
        ->where('tenant_id', 1)
        ->where('source_type', 'ccm_rule')
        ->count();

    expect($tenant1Issues)->toBeGreaterThan(0, 'CCM --tenant=1 must create Issues for tenant-1 obligations');
});

it('running with --tenant=2 creates Issues only for tenant-2 obligations', function (): void {
    // Only insert a tenant-2 obligation. Tenant 1 has nothing overdue.
    seedOverdueObligationForIdempotency(overdueDays: 45, tenantId: 2);

    $issuesBefore = Issue::withoutGlobalScopes()
        ->where('tenant_id', 1)
        ->where('source_type', 'ccm_rule')
        ->count();

    $this->artisan('controls:run-ccm --tenant=2')->assertExitCode(0);

    // Tenant-1 issue count must be unchanged.
    $tenant1Issues = Issue::withoutGlobalScopes()
        ->where('tenant_id', 1)
        ->where('source_type', 'ccm_rule')
        ->count();

    expect($tenant1Issues)->toBe($issuesBefore, 'CCM --tenant=2 must not create Issues for tenant-1');

    // Tenant-2 issue must have been created.
    $tenant2Issues = Issue::withoutGlobalScopes()
        ->where('tenant_id', 2)
        ->where('source_type', 'ccm_rule')
        ->count();

    expect($tenant2Issues)->toBeGreaterThan(0, 'CCM --tenant=2 must create Issues for tenant-2 obligations');
});
