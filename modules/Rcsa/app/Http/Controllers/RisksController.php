<?php

declare(strict_types=1);

namespace Modules\Rcsa\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Rcsa\Http\Requests\ScoreRiskRequest;
use Modules\Rcsa\Http\Requests\StoreRiskRequest;
use Modules\Rcsa\Http\Requests\UpdateRiskRequest;
use Modules\Rcsa\Models\Risk;
use Modules\Rcsa\Models\RiskAppetiteThreshold;
use Modules\Rcsa\Services\RcsaService;
use Modules\Rcsa\Services\RiskScoringService;

class RisksController extends Controller
{
    public function __construct(
        private readonly RcsaService $service,
        private readonly RiskScoringService $scoring,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', Risk::class);

        $cycleId = (int) $request->query('cycle');
        $filters = $request->only(['category', 'rating', 'appetite_breached']);

        $cycle = null;
        $paginated = null;

        if ($cycleId > 0) {
            $cycle = $this->service->find($cycleId);
            $paginated = $this->service->paginatedRisks($cycleId, $filters);
        }

        $thresholdMap = ($cycleId > 0)
            ? $this->scoring->thresholdMapForCycle($cycleId)
            : [];

        $risks = $paginated?->through(fn (Risk $r) => $this->formatRiskRow($r, $thresholdMap));

        return Inertia::render('Risks/Index', [
            'risks' => $risks,
            'cycle' => $cycle ? ['id' => $cycle->id, 'reference' => $cycle->reference, 'name' => $cycle->name] : null,
            'filters' => $filters,
            'categories' => $this->categoryOptions(),
            'ratings' => $this->ratingOptions(),
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $this->authorize('create', Risk::class);

        $cycleId = (int) $request->query('cycle');
        $cycle = $cycleId > 0 ? $this->service->find($cycleId) : null;

        return Inertia::render('Risks/Create', [
            'cycle' => $cycle ? ['id' => $cycle->id, 'reference' => $cycle->reference, 'name' => $cycle->name, 'methodology' => $cycle->methodology] : null,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(StoreRiskRequest $request): RedirectResponse
    {
        $this->authorize('create', Risk::class);

        $risk = $this->service->createRisk($request->validated());

        return redirect()->route('risks.show', $risk->id)
            ->with('flash', ['type' => 'success', 'message' => 'Risk created.']);
    }

    public function show(int $id): InertiaResponse
    {
        $risk = $this->service->findRisk($id);
        $this->authorize('view', $risk);

        $risk->loadMissing('cycle');

        $appetite = $this->scoring->appetiteFor($risk->cycle_id, $risk->category);
        $breaches = $this->scoring->breachesAppetite($risk->id);

        $cycle = $risk->cycle;
        $methodology = $cycle?->methodology ?? '3x3';

        $ratingThresholds = $methodology === '5x5'
            ? ['low' => 6, 'medium' => 12, 'high' => 20, 'critical' => 25]
            : ['low' => 2, 'medium' => 4, 'high' => 6, 'critical' => 9];

        $appetiteThreshold = null;
        if ($risk->cycle_id !== null) {
            $threshold = RiskAppetiteThreshold::withoutGlobalScopes()
                ->where('lob', $cycle?->lob)
                ->where('category', $risk->category)
                ->first();

            if ($threshold !== null) {
                $appetiteThreshold = [
                    'acceptable_rating' => $threshold->acceptable_rating,
                    'breach_action' => $threshold->breach_action,
                ];
            }
        }

        $user = auth()->user();

        return Inertia::render('Risks/Show', [
            'risk' => array_merge($this->formatRisk($risk), [
                'cycle' => $cycle ? [
                    'id' => $cycle->id,
                    'reference' => $cycle->reference,
                    'name' => $cycle->name,
                    'methodology' => $cycle->methodology,
                ] : null,
            ]),
            'methodology' => $methodology,
            'rating_thresholds' => $ratingThresholds,
            'appetite' => $appetiteThreshold,
            'breaches_appetite' => $breaches,
            'can' => [
                'edit' => $user?->can('update', $risk),
                'delete' => $user?->can('delete', $risk),
                'score' => $user?->can('score', $risk),
            ],
        ]);
    }

    public function edit(int $id): InertiaResponse
    {
        $risk = $this->service->findRisk($id);
        $this->authorize('update', $risk);

        $risk->loadMissing('cycle');

        return Inertia::render('Risks/Edit', [
            'risk' => $this->formatRisk($risk),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(UpdateRiskRequest $request, int $id): RedirectResponse
    {
        $risk = $this->service->findRisk($id);
        $this->authorize('update', $risk);

        $this->service->updateRisk($risk, $request->validated());

        return redirect()->route('risks.show', $risk->id)
            ->with('flash', ['type' => 'success', 'message' => 'Risk updated.']);
    }

    public function destroy(int $id): RedirectResponse
    {
        $risk = $this->service->findRisk($id);
        $this->authorize('delete', $risk);

        $risk->loadMissing('cycle');

        $cycleState = $risk->cycle?->state::$name ?? 'planning';
        $deletableStates = ['planning', 'data_capture'];

        if (! in_array($cycleState, $deletableStates, true)) {
            abort(403, 'Risks can only be deleted while the cycle is in Planning or Data Capture state.');
        }

        $cycleId = $risk->cycle_id;
        $risk->delete();

        return redirect()->route('risk-assessments.show', $cycleId)
            ->with('flash', ['type' => 'success', 'message' => 'Risk deleted.']);
    }

    public function score(ScoreRiskRequest $request, int $id): RedirectResponse
    {
        $risk = $this->service->findRisk($id);
        $this->authorize('score', $risk);

        $type = $request->validated('type');
        $likelihood = (int) $request->validated('likelihood');
        $impact = (int) $request->validated('impact');

        $updateData = $type === 'inherent'
            ? ['inherent_likelihood' => $likelihood, 'inherent_impact' => $impact]
            : ['residual_likelihood' => $likelihood, 'residual_impact' => $impact];

        $this->service->updateRisk($risk, $updateData);

        return back()->with('flash', ['type' => 'success', 'message' => 'Score saved.']);
    }

    /** @return array<string, mixed> */
    private function formatRisk(Risk $risk): array
    {
        return [
            'id' => $risk->id,
            'reference' => $risk->reference,
            'cycle_id' => $risk->cycle_id,
            'title' => $risk->title,
            'description' => $risk->description,
            'category' => $risk->category,
            'category_label' => $risk->categoryLabel(),
            'risk_owner' => $risk->risk_owner,
            'inherent_likelihood' => $risk->inherent_likelihood,
            'inherent_impact' => $risk->inherent_impact,
            'inherent_score' => $risk->inherent_score,
            'inherent_rating' => $risk->inherent_rating,
            'residual_likelihood' => $risk->residual_likelihood,
            'residual_impact' => $risk->residual_impact,
            'residual_score' => $risk->residual_score,
            'residual_rating' => $risk->residual_rating,
            'linked_obligation_ids' => $risk->linked_obligation_ids,
            'mitigation_summary' => $risk->mitigation_summary,
            'accept_basis' => $risk->accept_basis,
            'created_at' => $risk->created_at?->toIso8601String(),
            'updated_at' => $risk->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, array{acceptable_rating: string, breach_action: string|null}>  $thresholdMap
     * @return array<string, mixed>
     */
    private function formatRiskRow(Risk $risk, array $thresholdMap = []): array
    {
        return [
            'id' => $risk->id,
            'reference' => $risk->reference,
            'title' => $risk->title,
            'category' => $risk->category,
            'category_label' => $risk->categoryLabel(),
            'risk_owner' => $risk->risk_owner,
            'inherent_score' => $risk->inherent_score,
            'inherent_rating' => $risk->inherent_rating,
            'residual_score' => $risk->residual_score,
            'residual_rating' => $risk->residual_rating,
            'breaches_appetite' => $thresholdMap !== []
                ? $this->scoring->breachesAppetiteForLoadedRisk($risk, $thresholdMap)
                : $this->scoring->breachesAppetite($risk->id),
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function categoryOptions(): array
    {
        return [
            ['value' => 'operational', 'label' => 'Operational'],
            ['value' => 'credit', 'label' => 'Credit'],
            ['value' => 'market', 'label' => 'Market'],
            ['value' => 'liquidity', 'label' => 'Liquidity'],
            ['value' => 'compliance', 'label' => 'Compliance'],
            ['value' => 'reputational', 'label' => 'Reputational'],
            ['value' => 'strategic', 'label' => 'Strategic'],
            ['value' => 'cyber', 'label' => 'Cyber'],
            ['value' => 'aml', 'label' => 'AML & CFT'],
            ['value' => 'conduct', 'label' => 'Conduct'],
            ['value' => 'other', 'label' => 'Other'],
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function ratingOptions(): array
    {
        return [
            ['value' => 'low', 'label' => 'Low'],
            ['value' => 'medium', 'label' => 'Medium'],
            ['value' => 'high', 'label' => 'High'],
            ['value' => 'critical', 'label' => 'Critical'],
        ];
    }
}
