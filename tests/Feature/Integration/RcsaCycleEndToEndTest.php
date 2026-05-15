<?php

declare(strict_types=1);

/**
 * Scenario 3: RCSA cycle full end-to-end flow (cross-phase seam test).
 *
 * Exercises the wiring between:
 *   - Rcsa module (RiskAssessmentsController, RisksController, RcsaService,
 *     RiskScoringService, state machine, RiskAppetiteThreshold)
 *   - Audit module (state_transitioned events, risk.created, risk.updated)
 *   - RBAC (compliance_officer creates + transitions, risk_owner scores)
 *
 * State graph under test:
 *   planning → data_capture → scoring → in_review → signed_off → closed
 *
 * Also verifies:
 *   - Residual scores are recomputed by the saving hook on the Risk model.
 *   - The Inertia Show page exposes a correctly shaped heatmap prop.
 *   - Risks cannot be deleted once cycle leaves data_capture.
 *   - Risks cannot be edited in signed_off state.
 */

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Modules\Rcsa\Models\Risk;
use Modules\Rcsa\Models\RiskAppetiteThreshold;
use Modules\Rcsa\Models\RiskAssessmentCycle;
use Modules\Rcsa\Services\RcsaService;

beforeEach(function (): void {
    (new RolesAndPermissionsSeeder)->run();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create a cycle via the Eloquent model (bypasses HTTP) to establish a known
 * starting state.  Returns the persisted cycle.
 */
function createCycleInState(string $state, array $overrides = []): RiskAssessmentCycle
{
    $cycle = RiskAssessmentCycle::create(array_merge([
        'name'          => 'E2E Cycle ' . uniqid(),
        'lob'           => 'Retail',
        'cycle_year'    => 2026,
        'cycle_quarter' => 2,
        'methodology'   => '3x3',
        'state'         => $state,
    ], $overrides));

    return $cycle;
}

/**
 * Insert a RiskAppetiteThreshold row for the given lob + category.
 */
function seedAppetiteThreshold(string $lob, string $category, string $acceptableRating, int $tenantId = 1): void
{
    DB::table('risk_appetite_thresholds')->insert([
        'tenant_id'         => $tenantId,
        'lob'               => $lob,
        'category'          => $category,
        'acceptable_rating' => $acceptableRating,
        'breach_action'     => 'Escalate to board.',
    ]);
}

/**
 * Transition a cycle via HTTP POST as the given role.
 */
function transitionCycle(mixed $test, RiskAssessmentCycle $cycle, string $to, string $role): \Illuminate\Testing\TestResponse
{
    return $test->actingAsRole($role)
        ->post("/risk-assessments/{$cycle->id}/transition", ['to' => $to]);
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

it('compliance_officer creates a cycle in planning state via HTTP', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('compliance_officer');

    $this->actingAs($user)
        ->post('/risk-assessments', [
            'name'          => 'Retail RCSA 2026 Q2',
            'lob'           => 'Retail',
            'cycle_year'    => 2026,
            'cycle_quarter' => 2,
            'methodology'   => '3x3',
        ])
        ->assertRedirect();

    $cycle = RiskAssessmentCycle::withoutGlobalScopes()
        ->where('name', 'Retail RCSA 2026 Q2')
        ->firstOrFail();

    expect($cycle->state::$name)->toBe('planning');
});

it('compliance_officer transitions planning → data_capture and audit event is written', function (): void {
    $cycle = createCycleInState('planning');
    $auditBefore = DB::table('audit_events')
        ->where('subject_type', 'Modules\Rcsa\Models\RiskAssessmentCycle')
        ->where('subject_id', $cycle->id)
        ->count();

    transitionCycle($this, $cycle, 'data_capture', 'compliance_officer')
        ->assertRedirect("/risk-assessments/{$cycle->id}");

    expect($cycle->fresh()->state::$name)->toBe('data_capture');

    $auditAfter = DB::table('audit_events')
        ->where('subject_type', 'Modules\Rcsa\Models\RiskAssessmentCycle')
        ->where('subject_id', $cycle->id)
        ->count();

    expect($auditAfter)->toBeGreaterThan($auditBefore, 'Expected at least one audit event after transition');
});

it('risk_owner can create risks inside a data_capture cycle', function (): void {
    $cycle = createCycleInState('data_capture');

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('risk_owner');

    $categories = ['operational', 'credit', 'compliance'];
    foreach ($categories as $category) {
        $this->actingAs($user)
            ->post('/risks', [
                'cycle_id'    => $cycle->id,
                'title'       => "Risk: {$category} " . uniqid(),
                'description' => "Integration test risk for category {$category}.",
                'category'    => $category,
                'risk_owner'  => 'Risk Team',
            ])
            ->assertRedirect();
    }

    expect($cycle->risks()->count())->toBe(3, 'Expected 3 risks to be created');
});

it('data_capture → scoring transition is blocked when no risks exist', function (): void {
    $cycle = createCycleInState('data_capture');

    transitionCycle($this, $cycle, 'scoring', 'compliance_officer')
        ->assertRedirect()
        ->assertSessionHasErrors('transition');

    expect($cycle->fresh()->state::$name)->toBe('data_capture');
});

it('risk cannot be deleted once cycle is in scoring state', function (): void {
    $cycle = createCycleInState('data_capture');

    $risk = Risk::create([
        'cycle_id'    => $cycle->id,
        'title'       => 'Risk to attempt deletion',
        'description' => 'Delete guard test.',
        'category'    => 'operational',
    ]);

    // Move cycle to scoring (compliance_officer — has cycles.transition).
    $service = app(RcsaService::class);
    $service->transitionCycle($cycle, 'scoring');

    expect($cycle->fresh()->state::$name)->toBe('scoring');

    // risk_owner has risks.delete permission but the controller enforces the
    // cycle-state guard — abort(403) when not in planning or data_capture.
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('risk_owner');

    $this->actingAs($user)
        ->delete("/risks/{$risk->id}")
        ->assertForbidden();

    expect(Risk::withoutGlobalScopes()->find($risk->id))->not->toBeNull('Risk must not have been deleted');
});

it('residual score and rating are recomputed by the saving hook on the Risk model', function (): void {
    $cycle = createCycleInState('scoring', ['methodology' => '3x3']);

    $risk = Risk::create([
        'cycle_id'    => $cycle->id,
        'title'       => 'Score hook test risk',
        'description' => 'Checking the saving hook computes residual_score and residual_rating.',
        'category'    => 'market',
    ]);

    // Score via the HTTP endpoint (compliance_officer has risks.score).
    $this->actingAsRole('compliance_officer')
        ->post("/risks/{$risk->id}/score", [
            'type'       => 'residual',
            'likelihood' => 2,
            'impact'     => 3,
        ])
        ->assertRedirect();

    $fresh = $risk->fresh();
    // 3x3: score = 2 * 3 = 6; rating = high (>4, <=6)
    expect($fresh->residual_score)->toBe(6, 'Residual score must be likelihood × impact = 6');
    expect($fresh->residual_rating)->toBe('high', 'Score 6 on 3×3 must map to high');
});

it('heatmap prop returned by Show page has the correct 3x3 matrix shape', function (): void {
    $cycle = createCycleInState('scoring', ['methodology' => '3x3']);

    // Seed 3 risks with known residual_likelihood / residual_impact values.
    Risk::create([
        'cycle_id'            => $cycle->id,
        'title'               => 'Heatmap risk 1',
        'description'         => 'Desc',
        'category'            => 'operational',
        'residual_likelihood' => 1,
        'residual_impact'     => 1,
    ]);
    Risk::create([
        'cycle_id'            => $cycle->id,
        'title'               => 'Heatmap risk 2',
        'description'         => 'Desc',
        'category'            => 'credit',
        'residual_likelihood' => 2,
        'residual_impact'     => 3,
    ]);
    Risk::create([
        'cycle_id'            => $cycle->id,
        'title'               => 'Heatmap risk 3',
        'description'         => 'Desc',
        'category'            => 'compliance',
        'residual_likelihood' => 3,
        'residual_impact'     => 3,
    ]);

    $response = $this->actingAsRole('compliance_officer')
        ->get("/risk-assessments/{$cycle->id}");

    $response->assertOk();

    $response->assertInertia(function ($page): void {
        $page->has('heatmap')
            ->has('heatmap.matrix')
            ->has('heatmap.size')
            ->where('heatmap.size', 3);

        // The matrix must be a 3×3 structure of non-negative integers.
        $page->where('heatmap.matrix', function ($matrix): bool {
            // The Inertia test fluent API may pass a Collection or plain array.
            $matrix = is_array($matrix) ? $matrix : $matrix->toArray();

            if (count($matrix) !== 3) {
                return false;
            }
            foreach ($matrix as $row) {
                $row = is_array($row) ? $row : (array) $row;
                if (count($row) !== 3) {
                    return false;
                }
                foreach ($row as $cell) {
                    if (! is_int($cell) || $cell < 0) {
                        return false;
                    }
                }
            }
            return true;
        });
    });
});

it('full cycle transitions planning → data_capture → scoring → in_review → signed_off → closed all succeed with audit events', function (): void {
    // Seed appetite thresholds for the lob used by the cycle.
    seedAppetiteThreshold('Operations', 'operational', 'medium');

    $cycle = createCycleInState('planning', ['lob' => 'Operations']);

    $service = app(RcsaService::class);

    // planning → data_capture
    $service->transitionCycle($cycle, 'data_capture');
    expect($cycle->fresh()->state::$name)->toBe('data_capture');

    // Add a risk so the data_capture → scoring transition is allowed.
    Risk::create([
        'cycle_id'            => $cycle->id,
        'title'               => 'Full-flow risk',
        'description'         => 'Risk for full flow test.',
        'category'            => 'operational',
        'inherent_likelihood' => 2,
        'inherent_impact'     => 2,
        'residual_likelihood' => 1,
        'residual_impact'     => 1,
    ]);

    // data_capture → scoring
    $service->transitionCycle($cycle, 'scoring');
    expect($cycle->fresh()->state::$name)->toBe('scoring');

    // scoring → in_review (all risks have scores — satisfied by the risk above).
    $service->transitionCycle($cycle, 'in_review');
    expect($cycle->fresh()->state::$name)->toBe('in_review');

    // in_review → signed_off requires lead_assessor_id.
    $assessor = User::factory()->create(['email_verified_at' => now()]);
    $cycle->lead_assessor_id = $assessor->id;
    $cycle->saveQuietly();

    $service->transitionCycle($cycle, 'signed_off');
    expect($cycle->fresh()->state::$name)->toBe('signed_off');

    // signed_off → closed
    $service->transitionCycle($cycle, 'closed');
    expect($cycle->fresh()->state::$name)->toBe('closed');

    // At least 5 state_transitioned audit events for this cycle
    // (one per transition, written by RcsaService::transitionCycle via recordAudit).
    $auditCount = DB::table('audit_events')
        ->where('subject_type', 'Modules\Rcsa\Models\RiskAssessmentCycle')
        ->where('subject_id', $cycle->id)
        ->where('action', 'state_transitioned')
        ->count();

    expect($auditCount)->toBeGreaterThanOrEqual(5, "Expected at least 5 state_transitioned audit events; got {$auditCount}");
});

it('risk cannot be deleted in signed_off state', function (): void {
    $cycle = createCycleInState('signed_off');

    $risk = Risk::create([
        'cycle_id'    => $cycle->id,
        'title'       => 'Risk in signed-off cycle',
        'description' => 'Should not be deletable.',
        'category'    => 'compliance',
    ]);

    // compliance_officer has risks.delete but the controller guards on cycle state.
    $this->actingAsRole('compliance_officer')
        ->delete("/risks/{$risk->id}")
        ->assertForbidden();

    expect(Risk::withoutGlobalScopes()->find($risk->id))->not->toBeNull();
});

it('scoring → in_review is blocked when a risk has no residual score', function (): void {
    $cycle = createCycleInState('scoring');

    // Risk with no residual scores (inherent only).
    Risk::create([
        'cycle_id'            => $cycle->id,
        'title'               => 'Unscored residual risk',
        'description'         => 'Missing residual scores.',
        'category'            => 'operational',
        'inherent_likelihood' => 2,
        'inherent_impact'     => 2,
    ]);

    transitionCycle($this, $cycle, 'in_review', 'compliance_officer')
        ->assertRedirect()
        ->assertSessionHasErrors('transition');

    expect($cycle->fresh()->state::$name)->toBe('scoring');
});
