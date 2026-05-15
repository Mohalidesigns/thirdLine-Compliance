<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Rcsa\Models\Risk;
use Modules\Rcsa\Models\RiskAppetiteThreshold;
use Modules\Rcsa\Models\RiskAssessmentCycle;
use Modules\Rcsa\Services\RcsaService;
use Modules\Rcsa\Services\RiskScoringService;

uses(RefreshDatabase::class);

function makeRcsaUser(): User
{
    return User::factory()->create();
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
