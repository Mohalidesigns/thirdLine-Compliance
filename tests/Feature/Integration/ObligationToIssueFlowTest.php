<?php

declare(strict_types=1);

/**
 * Scenario 1: Obligation → CCM rule → Issue lifecycle (cross-phase seam test).
 *
 * Exercises the wiring between:
 *   - Library module (Obligation)
 *   - Controls module (RunCcmRulesCommand, Issue, CcmRuleRun)
 *   - Audit module (audit_events table via AuditWriter + EmitsAuditEvent)
 *   - RBAC (control_tester transitions, risk_owner is blocked)
 *
 * The CCM command design is "one issue per rule per run" — each invocation of
 * controls:run-ccm that detects a breach creates a new Issue. See CcmIdempotencyTest
 * for the idempotency contract test.
 */

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Modules\Controls\Models\CcmRuleRun;
use Modules\Controls\Models\Issue;

beforeEach(function (): void {
    (new RolesAndPermissionsSeeder)->run();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Insert the minimal reference-data rows required by the instruments foreign
 * key chain and return the new obligation's primary key.
 */
function seedOverdueObligation(int $overdueDays = 45, int $tenantId = 1): int
{
    $regulatorId = DB::table('regulators')->insertGetId([
        'code'       => 'REG-OBL-' . uniqid(),
        'name'       => 'Obligation Flow Regulator',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $typeId = DB::table('instrument_types')->insertGetId([
        'name'       => 'OblFlowType-' . uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $natureId = DB::table('natures')->insertGetId([
        'name'       => 'OblFlowNature-' . uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $statusId = DB::table('statuses')->insertGetId([
        'name'       => 'OblFlowStatus-' . uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $areaId = DB::table('areas_of_focus')->insertGetId([
        'name'       => 'OblFlowArea-' . uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $ratingId = DB::table('risk_ratings')->insertGetId([
        'name'       => 'OblFlowRating-' . uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $instrumentId = DB::table('instruments')->insertGetId([
        'tenant_id'          => $tenantId,
        'source_title'       => 'OblFlow Instrument ' . uniqid(),
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
        'reference'     => 'OBL-FLOW-' . uniqid(),
        'title'         => 'Overdue Obligation (flow test)',
        'description'   => 'An obligation that is overdue for integration testing.',
        'next_due_date' => now()->subDays($overdueDays)->toDateString(),
        'status'        => 'open',
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

it('CCM command creates one Issue with source_type=ccm_rule, correct severity, and linked_obligation_id for a 45-day overdue obligation', function (): void {
    // 45 days falls in the 30–90 bucket → medium severity.
    $obligationId = seedOverdueObligation(overdueDays: 45);

    $issuesBefore   = Issue::withoutGlobalScopes()->count();
    $runsBefore     = DB::table('ccm_rule_runs')->count();
    $auditBefore    = DB::table('audit_events')->count();

    $this->artisan('controls:run-ccm')->assertExitCode(0);

    // One new ccm_rule_run row must exist for the overdue_obligations rule.
    expect(DB::table('ccm_rule_runs')
        ->where('rule_name', 'overdue_obligations')
        ->where('status', 'threshold_breached')
        ->count()
    )->toBeGreaterThan(0, 'Expected at least one breached ccm_rule_run for overdue_obligations');

    // Exactly one new issue should have been created for the breach.
    $newIssues = Issue::withoutGlobalScopes()
        ->where('source_type', 'ccm_rule')
        ->where('linked_obligation_id', $obligationId)
        ->get();

    expect($newIssues)->toHaveCount(1, 'Expected exactly one Issue linked to the overdue obligation');

    $issue = $newIssues->first();
    expect($issue->source_type)->toBe('ccm_rule');
    expect($issue->severity)->toBe('medium', '45 days overdue must map to medium severity');
    expect($issue->status)->toBe('open');
    expect($issue->linked_obligation_id)->toBe($obligationId);

    // Audit trail: at minimum one event for the ccm threshold breach + one for the issue creation.
    expect(DB::table('audit_events')->count())->toBeGreaterThan(
        $auditBefore,
        'Expected new audit events after CCM run'
    );

    // There must be a ccm.threshold_breached audit event.
    expect(DB::table('audit_events')
        ->where('action', 'ccm.threshold_breached')
        ->count()
    )->toBeGreaterThan(0, 'Expected a ccm.threshold_breached audit event');
});

it('control_tester can walk an issue through open → in_progress → resolved → closed and each transition writes an audit event', function (): void {
    // Seed an issue that was created by CCM.
    $obligationId = seedOverdueObligation(overdueDays: 45);
    $this->artisan('controls:run-ccm')->assertExitCode(0);

    $issue = Issue::withoutGlobalScopes()
        ->where('source_type', 'ccm_rule')
        ->where('linked_obligation_id', $obligationId)
        ->firstOrFail();

    // The issue audit events start after CCM run (created event at minimum).
    $issueAuditBefore = DB::table('audit_events')
        ->where('subject_type', 'Modules\Controls\Models\Issue')
        ->where('subject_id', $issue->id)
        ->count();

    expect($issueAuditBefore)->toBeGreaterThanOrEqual(1, 'Issue creation must have produced at least one audit event');

    // Step 1: open → in_progress
    $this->actingAsRole('control_tester')
        ->patch("/issues/{$issue->id}", ['status' => 'in_progress'])
        ->assertRedirect();

    expect($issue->fresh()->status)->toBe('in_progress');

    // Step 2: in_progress → resolved (resolution notes required)
    $this->actingAsRole('control_tester')
        ->patch("/issues/{$issue->id}", [
            'status'           => 'resolved',
            'resolution_notes' => 'Fixed by extending due date',
        ])
        ->assertRedirect();

    expect($issue->fresh()->status)->toBe('resolved');

    // Step 3: resolved → closed (resolution notes required)
    $this->actingAsRole('control_tester')
        ->patch("/issues/{$issue->id}", [
            'status'           => 'closed',
            'resolution_notes' => 'Fixed by extending due date',
        ])
        ->assertRedirect();

    $freshIssue = $issue->fresh();
    expect($freshIssue->status)->toBe('closed');

    // Total audit events for this issue must be at least 4:
    //   1 = issue.created (EmitsAuditEvent on CCM run)
    //   3 = issue.updated (one per transition — EmitsAuditEvent fires on update)
    $issueAuditAfter = DB::table('audit_events')
        ->where('subject_type', 'Modules\Controls\Models\Issue')
        ->where('subject_id', $issue->id)
        ->count();

    expect($issueAuditAfter)->toBeGreaterThanOrEqual(4, 'Expected at least 4 audit events for the issue (1 created + 3 transitions)');
});

it('risk_owner cannot transition an issue and receives a 403', function (): void {
    $obligationId = seedOverdueObligation(overdueDays: 45);
    $this->artisan('controls:run-ccm')->assertExitCode(0);

    $issue = Issue::withoutGlobalScopes()
        ->where('source_type', 'ccm_rule')
        ->where('linked_obligation_id', $obligationId)
        ->firstOrFail();

    // risk_owner has issues.view but NOT issues.transition.
    $this->actingAsRole('risk_owner')
        ->patch("/issues/{$issue->id}", ['status' => 'in_progress'])
        ->assertForbidden();

    // The issue must remain open.
    expect($issue->fresh()->status)->toBe('open');
});
