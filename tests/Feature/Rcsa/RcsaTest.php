<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Rcsa\Models\Risk;
use Modules\Rcsa\Models\RiskAppetiteThreshold;
use Modules\Rcsa\Models\RiskAssessmentCycle;
use Modules\Rcsa\Services\RcsaService;
use Modules\Rcsa\Services\RiskScoringService;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

/**
 * Returns a compliance_officer who can perform all RCSA actions.
 */
function makeRcsaUser(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('compliance_officer');

    return $user;
}

function makeCycle(array $overrides = []): RiskAssessmentCycle
{
    return RiskAssessmentCycle::create(array_merge([
        'name' => 'Test RCSA Cycle',
        'lob' => 'Retail',
        'cycle_year' => 2026,
        'cycle_quarter' => 1,
        'methodology' => '3x3',
    ], $overrides));
}

function makeRisk(RiskAssessmentCycle $cycle, array $overrides = []): Risk
{
    return Risk::create(array_merge([
        'cycle_id' => $cycle->id,
        'title' => 'Test Risk',
        'description' => 'A test risk description.',
        'category' => 'operational',
        'linked_obligation_ids' => [],
    ], $overrides));
}

it('scores 3x3 matrix correctly', function () {
    $service = app(RiskScoringService::class);

    $cases = [
        [1, 1, 1, 'low'],
        [1, 2, 2, 'low'],
        [2, 1, 2, 'low'],
        [2, 2, 4, 'medium'],
        [2, 3, 6, 'high'],
        [3, 2, 6, 'high'],
        [3, 3, 9, 'critical'],
    ];

    foreach ($cases as [$likelihood, $impact, $expectedScore, $expectedRating]) {
        $result = $service->scoreFor($likelihood, $impact, '3x3');
        expect($result['score'])->toBe($expectedScore, "score mismatch for {$likelihood}x{$impact}");
        expect($result['rating'])->toBe($expectedRating, "rating mismatch for {$likelihood}x{$impact}");
    }
});

it('scores 5x5 matrix correctly', function () {
    $service = app(RiskScoringService::class);

    $cases = [
        [1, 1, 1, 'low'],
        [2, 3, 6, 'low'],
        [2, 4, 8, 'medium'],
        [3, 4, 12, 'medium'],
        [3, 5, 15, 'high'],
        [4, 5, 20, 'high'],
        [5, 5, 25, 'critical'],
        [4, 6, 24, 'critical'],
    ];

    foreach ($cases as [$likelihood, $impact, $expectedScore, $expectedRating]) {
        $result = $service->scoreFor($likelihood, $impact, '5x5');
        expect($result['score'])->toBe($expectedScore, "score mismatch for {$likelihood}x{$impact}");
        expect($result['rating'])->toBe($expectedRating, "rating mismatch for {$likelihood}x{$impact}");
    }
});

it('persists inherent and residual scores on save', function () {
    $cycle = makeCycle(['methodology' => '3x3']);

    $risk = makeRisk($cycle, [
        'inherent_likelihood' => 3,
        'inherent_impact' => 3,
        'residual_likelihood' => 2,
        'residual_impact' => 2,
    ]);

    expect($risk->inherent_score)->toBe(9);
    expect($risk->inherent_rating)->toBe('critical');
    expect($risk->residual_score)->toBe(4);
    expect($risk->residual_rating)->toBe('medium');

    $risk->refresh();
    expect($risk->inherent_score)->toBe(9);
    expect($risk->residual_rating)->toBe('medium');
});

it('detects appetite breach correctly', function () {
    $service = app(RiskScoringService::class);

    $cycle = makeCycle(['lob' => 'Retail', 'methodology' => '3x3']);

    RiskAppetiteThreshold::create([
        'tenant_id' => 1,
        'lob' => 'Retail',
        'category' => 'aml',
        'acceptable_rating' => 'medium',
        'breach_action' => 'Escalate to BRC',
    ]);

    $riskNotBreaching = makeRisk($cycle, [
        'category' => 'aml',
        'residual_likelihood' => 2,
        'residual_impact' => 2,
    ]);
    expect($riskNotBreaching->residual_rating)->toBe('medium');
    expect($service->breachesAppetite($riskNotBreaching->id))->toBeFalse();

    $riskBreaching = makeRisk($cycle, [
        'category' => 'aml',
        'residual_likelihood' => 3,
        'residual_impact' => 3,
    ]);
    expect($riskBreaching->residual_rating)->toBe('critical');
    expect($service->breachesAppetite($riskBreaching->id))->toBeTrue();
});

it('blocks scoring transition if any risk lacks scores', function () {
    $user = makeRcsaUser();
    $cycle = makeCycle();

    DB::table('risk_assessment_cycles')->where('id', $cycle->id)->update(['state' => 'scoring']);

    makeRisk($cycle, ['inherent_likelihood' => 3, 'inherent_impact' => 2]);
    makeRisk($cycle);

    $this->actingAs($user)->post("/risk-assessments/{$cycle->id}/transition", [
        'to' => 'in_review',
    ])->assertRedirect();

    $cycle->refresh();
    $freshState = DB::table('risk_assessment_cycles')->where('id', $cycle->id)->value('state');
    expect($freshState)->toBe('scoring');
});

it('produces a heatmap with risks in correct cells (0-indexed counts)', function () {
    $service = app(RcsaService::class);
    $cycle = makeCycle(['methodology' => '3x3']);

    // Risk A: residual_impact=3, residual_likelihood=2 → matrix[2][1]
    makeRisk($cycle, [
        'residual_likelihood' => 2,
        'residual_impact' => 3,
        'inherent_likelihood' => 3,
        'inherent_impact' => 3,
    ]);

    // Risk B: residual_impact=1, residual_likelihood=1 → matrix[0][0]
    makeRisk($cycle, [
        'residual_likelihood' => 1,
        'residual_impact' => 1,
        'inherent_likelihood' => 2,
        'inherent_impact' => 2,
    ]);

    $heatmap = $service->heatmap($cycle->id);

    expect($heatmap['methodology'])->toBe('3x3');
    expect($heatmap['size'])->toBe(3);
    expect($heatmap['matrix'])->toBeArray();
    // matrix is 0-indexed: [impact-1][likelihood-1]
    expect($heatmap['matrix'][2][1])->toBe(1); // impact=3, likelihood=2
    expect($heatmap['matrix'][0][0])->toBe(1); // impact=1, likelihood=1
    // All other cells should be 0
    expect($heatmap['matrix'][1][1])->toBe(0);
});

// ─── Fix #2: Risk destroy state guards ───────────────────────────────────────

it('risk destroy succeeds when cycle is in planning state', function () {
    $user = makeRcsaUser();
    $cycle = makeCycle(); // defaults to planning
    $risk = makeRisk($cycle);

    $this->actingAs($user)
        ->delete("/risks/{$risk->id}")
        ->assertRedirect(route('risk-assessments.show', $cycle->id));

    expect(Risk::withTrashed()->find($risk->id)->trashed())->toBeTrue();
});

it('risk destroy succeeds when cycle is in data_capture state', function () {
    $user = makeRcsaUser();
    $cycle = makeCycle();
    DB::table('risk_assessment_cycles')->where('id', $cycle->id)->update(['state' => 'data_capture']);
    $risk = makeRisk($cycle);

    $this->actingAs($user)
        ->delete("/risks/{$risk->id}")
        ->assertRedirect(route('risk-assessments.show', $cycle->id));

    expect(Risk::withTrashed()->find($risk->id)->trashed())->toBeTrue();
});

it('risk destroy returns 403 when cycle is in scoring state and risk still exists', function () {
    $user = makeRcsaUser();
    $cycle = makeCycle();
    DB::table('risk_assessment_cycles')->where('id', $cycle->id)->update(['state' => 'scoring']);
    $risk = makeRisk($cycle);

    $this->actingAs($user)
        ->delete("/risks/{$risk->id}")
        ->assertForbidden();

    expect(Risk::find($risk->id))->not->toBeNull();
});

it('risk destroy returns 403 when cycle is signed_off and risk still exists', function () {
    $user = makeRcsaUser();
    $cycle = makeCycle();
    DB::table('risk_assessment_cycles')->where('id', $cycle->id)->update(['state' => 'signed_off']);
    $risk = makeRisk($cycle);

    $this->actingAs($user)
        ->delete("/risks/{$risk->id}")
        ->assertForbidden();

    expect(Risk::find($risk->id))->not->toBeNull();
});

it('emits one audit event per risk create', function () {
    $initialCount = DB::table('audit_events')->count();

    $cycle = makeCycle();
    makeRisk($cycle);

    $afterCycleAndRisk = DB::table('audit_events')->count();
    expect($afterCycleAndRisk - $initialCount)->toBe(2);

    expect(DB::table('audit_events')->where('action', 'risk.created')->count())->toBe(1);
});

it('creates a cycle and transitions through states correctly', function () {
    $user = makeRcsaUser();
    $cycle = makeCycle();

    $this->actingAs($user)->post("/risk-assessments/{$cycle->id}/transition", [
        'to' => 'data_capture',
    ])->assertRedirect();

    $freshState = DB::table('risk_assessment_cycles')->where('id', $cycle->id)->value('state');
    expect($freshState)->toBe('data_capture');
});

// ─── RBAC: wrong-role gets 403 ────────────────────────────────────────────────

it('risk_owner cannot transition a cycle', function () {
    $riskOwner = User::factory()->create(['email_verified_at' => now()]);
    $riskOwner->assignRole('risk_owner');
    $cycle = makeCycle();

    $this->actingAs($riskOwner)
        ->post("/risk-assessments/{$cycle->id}/transition", ['to' => 'data_capture'])
        ->assertForbidden();

    $freshState = DB::table('risk_assessment_cycles')->where('id', $cycle->id)->value('state');
    expect($freshState)->toBe('planning');
});

it('risk_owner can update a risk while cycle is in data_capture', function () {
    $riskOwner = User::factory()->create(['email_verified_at' => now()]);
    $riskOwner->assignRole('risk_owner');

    $cycle = makeCycle();
    DB::table('risk_assessment_cycles')->where('id', $cycle->id)->update(['state' => 'data_capture']);
    $risk = makeRisk($cycle);

    $this->actingAs($riskOwner)
        ->put("/risks/{$risk->id}", [
            'cycle_id' => $cycle->id,
            'title' => 'Updated Risk Title',
            'description' => 'Updated description',
            'category' => 'operational',
        ])
        ->assertRedirect(); // success
});

it('risk_owner cannot update a risk when cycle is past scoring', function () {
    $riskOwner = User::factory()->create(['email_verified_at' => now()]);
    $riskOwner->assignRole('risk_owner');

    $cycle = makeCycle();
    DB::table('risk_assessment_cycles')->where('id', $cycle->id)->update(['state' => 'in_review']);
    $risk = makeRisk($cycle);

    $this->actingAs($riskOwner)
        ->put("/risks/{$risk->id}", [
            'cycle_id' => $cycle->id,
            'title' => 'Updated Risk Title',
            'description' => 'Updated description',
            'category' => 'operational',
        ])
        ->assertForbidden();
});

it('control_tester cannot create a risk assessment cycle', function () {
    $tester = User::factory()->create(['email_verified_at' => now()]);
    $tester->assignRole('control_tester');

    $this->actingAs($tester)
        ->post('/risk-assessments', [
            'name' => 'Unauthorized cycle',
            'lob' => 'IT',
            'cycle_year' => 2026,
            'methodology' => '3x3',
        ])
        ->assertForbidden();
});

it('auditor can view risk assessments but cannot create a cycle', function () {
    $auditor = User::factory()->create(['email_verified_at' => now()]);
    $auditor->assignRole('auditor');

    $this->actingAs($auditor)->get('/risk-assessments')->assertOk();
    $this->actingAs($auditor)->post('/risk-assessments', [
        'name' => 'Unauthorized',
        'lob' => 'Retail',
        'cycle_year' => 2026,
        'methodology' => '3x3',
    ])->assertForbidden();
});

// ─── N+1 fix: query count does not scale with risk row count ──────────────────

it('cycle show page query count does not scale with number of risks (N+1 fix)', function () {
    $user = makeRcsaUser();
    $cycle = makeCycle(['lob' => 'Retail', 'methodology' => '3x3']);

    RiskAppetiteThreshold::create([
        'tenant_id' => 1,
        'lob' => 'Retail',
        'category' => 'operational',
        'acceptable_rating' => 'medium',
        'breach_action' => 'Escalate',
    ]);

    // Create 30 risks — all with scores to exercise the breach check.
    for ($i = 0; $i < 30; $i++) {
        makeRisk($cycle, [
            'category' => 'operational',
            'residual_likelihood' => 3,
            'residual_impact' => 3,
            'inherent_likelihood' => 3,
            'inherent_impact' => 3,
        ]);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($user)
        ->get("/risk-assessments/{$cycle->id}")
        ->assertOk();

    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Before the fix: ~75+ queries (3 per risk). After: < 25.
    expect($queryCount)->toBeLessThan(25, "Expected < 25 queries but got {$queryCount}. N+1 may still be present.");
});

it('breachesAppetiteForLoadedRisk is pure-logic with pre-built threshold map', function () {
    $service = app(RiskScoringService::class);

    $cycle = makeCycle(['lob' => 'Retail', 'methodology' => '3x3']);

    RiskAppetiteThreshold::create([
        'tenant_id' => 1,
        'lob' => 'Retail',
        'category' => 'aml',
        'acceptable_rating' => 'medium',
        'breach_action' => 'Escalate',
    ]);

    $map = $service->thresholdMapForCycle($cycle->id);

    $nonBreachingRisk = makeRisk($cycle, [
        'category' => 'aml',
        'residual_likelihood' => 2,
        'residual_impact' => 2, // score=4, rating=medium — at appetite, not above
    ]);

    $breachingRisk = makeRisk($cycle, [
        'category' => 'aml',
        'residual_likelihood' => 3,
        'residual_impact' => 3, // score=9, rating=critical — above medium appetite
    ]);

    // These must produce zero additional DB queries.
    DB::flushQueryLog();
    DB::enableQueryLog();

    $r1 = $service->breachesAppetiteForLoadedRisk($nonBreachingRisk, $map);
    $r2 = $service->breachesAppetiteForLoadedRisk($breachingRisk, $map);

    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($r1)->toBeFalse();
    expect($r2)->toBeTrue();
    expect($queryCount)->toBe(0, "breachesAppetiteForLoadedRisk should issue zero queries but issued {$queryCount}.");
});
