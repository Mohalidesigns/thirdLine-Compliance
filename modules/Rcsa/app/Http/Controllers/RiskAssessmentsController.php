<?php

declare(strict_types=1);

namespace Modules\Rcsa\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Rcsa\Http\Requests\StoreRiskAssessmentCycleRequest;
use Modules\Rcsa\Http\Requests\TransitionCycleRequest;
use Modules\Rcsa\Http\Requests\UpdateRiskAssessmentCycleRequest;
use Modules\Rcsa\Models\RiskAssessmentCycle;
use Modules\Rcsa\Models\RiskWorkshopNote;
use Modules\Rcsa\Services\RcsaService;
use Modules\Rcsa\Services\RiskScoringService;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

class RiskAssessmentsController extends Controller
{
    public function __construct(
        private readonly RcsaService $service,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $filters = $request->only(['search', 'lob', 'state', 'year']);
        $paginated = $this->service->paginatedCycles($filters);

        $cycles = $paginated->through(fn (RiskAssessmentCycle $c) => [
            'id' => $c->id,
            'reference' => $c->reference,
            'name' => $c->name,
            'lob' => $c->lob,
            'state' => $c->state::$name,
            'state_label' => $c->stateLabel(),
            'state_color' => $c->stateColor(),
            'cycle_year' => $c->cycle_year,
            'cycle_quarter' => $c->cycle_quarter,
            'methodology' => $c->methodology,
            'sla_due_date' => $c->sla_due_date?->format('Y-m-d'),
            'sla_days_remaining' => $c->slaDaysRemaining(),
            'risks_count' => $c->risks_count,
            'high_risk_count' => $c->high_risk_count,
            'lead_assessor_name' => $c->leadAssessor?->name,
            'updated_at' => $c->updated_at?->toIso8601String(),
        ]);

        $years = RiskAssessmentCycle::query()
            ->selectRaw('DISTINCT cycle_year')
            ->orderByDesc('cycle_year')
            ->pluck('cycle_year')
            ->toArray();

        return Inertia::render('RiskAssessments/Index', [
            'cycles' => $cycles,
            'filters' => $filters,
            'lobs' => $this->lobOptions(),
            'states' => $this->stateOptions(),
            'years' => $years,
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('RiskAssessments/Create', [
            'lobs' => $this->lobOptions(),
            'methodologies' => [
                ['value' => '3x3', 'label' => '3×3 Matrix'],
                ['value' => '5x5', 'label' => '5×5 Matrix'],
            ],
        ]);
    }

    public function store(StoreRiskAssessmentCycleRequest $request): RedirectResponse
    {
        $cycle = $this->service->createCycle($request->validated());

        return redirect()->route('risk-assessments.show', $cycle->id)
            ->with('flash', ['type' => 'success', 'message' => 'Risk assessment cycle created.']);
    }

    public function show(int $id): InertiaResponse
    {
        $cycle = $this->service->find($id);
        $cycle->loadMissing([
            'leadAssessor',
            'workshopNotes' => fn ($q) => $q->with('recordedBy:id,name')->orderByDesc('recorded_at')->limit(5),
        ]);

        $summary = $this->service->cycleSummary($id);
        $heatmap = $this->service->heatmap($id);
        $risks = $this->service->paginatedRisks($id, []);

        $scoringService = app(RiskScoringService::class);

        $risksData = $risks->through(fn ($r) => [
            'id' => $r->id,
            'reference' => $r->reference,
            'title' => $r->title,
            'category' => $r->category,
            'category_label' => $r->categoryLabel(),
            'risk_owner' => $r->risk_owner,
            'inherent_score' => $r->inherent_score,
            'inherent_rating' => $r->inherent_rating,
            'residual_score' => $r->residual_score,
            'residual_rating' => $r->residual_rating,
            'breaches_appetite' => $scoringService->breachesAppetite($r->id),
        ]);

        return Inertia::render('RiskAssessments/Show', [
            'cycle' => array_merge($this->formatCycle($cycle), [
                'lead_assessor_name' => $cycle->leadAssessor?->name,
                'workshop_notes' => $cycle->workshopNotes->map(fn ($n) => [
                    'id' => $n->id,
                    'recorded_at' => $n->recorded_at?->toIso8601String(),
                    'recorded_by_name' => $n->recordedBy?->name,
                    'notes' => $n->notes,
                    'attendees' => $n->attendees ?? [],
                ])->toArray(),
            ]),
            'summary' => $summary,
            'heatmap' => $heatmap,
            'risks' => $risksData,
            'allowed_transitions' => $cycle->allowedTransitions(),
            'can' => [
                'edit' => in_array($cycle->state::$name, ['planning', 'data_capture']),
                'transition' => true,
                'delete' => $cycle->state::$name === 'planning',
                'add_risk' => in_array($cycle->state::$name, ['data_capture', 'scoring']),
                'add_workshop' => true,
            ],
        ]);
    }

    public function edit(int $id): InertiaResponse
    {
        $cycle = $this->service->find($id);

        return Inertia::render('RiskAssessments/Edit', [
            'cycle' => $this->formatCycle($cycle),
            'lobs' => $this->lobOptions(),
            'methodologies' => [
                ['value' => '3x3', 'label' => '3×3 Matrix'],
                ['value' => '5x5', 'label' => '5×5 Matrix'],
            ],
        ]);
    }

    public function update(UpdateRiskAssessmentCycleRequest $request, int $id): RedirectResponse
    {
        $cycle = $this->service->find($id);
        $this->service->updateCycle($cycle, $request->validated());

        return redirect()->route('risk-assessments.show', $cycle->id)
            ->with('flash', ['type' => 'success', 'message' => 'Cycle updated.']);
    }

    public function destroy(int $id): RedirectResponse
    {
        $cycle = $this->service->find($id);

        if ($cycle->state::$name !== 'planning') {
            abort(403, 'Only cycles in Planning state can be deleted.');
        }

        $cycle->delete();

        return redirect()->route('risk-assessments.index')
            ->with('flash', ['type' => 'success', 'message' => 'Cycle deleted.']);
    }

    public function transition(TransitionCycleRequest $request, int $id): RedirectResponse
    {
        $cycle = $this->service->find($id);
        $to = $request->validated('to');
        $workshopNote = $request->validated('workshop_note');

        $allowedTransition = collect($cycle->allowedTransitions())->firstWhere('to', $to);

        if ($allowedTransition === null) {
            return back()->withErrors(['transition' => 'This transition is not allowed from the current state.']);
        }

        if ($to === 'scoring' && $cycle->state::$name === 'data_capture') {
            $riskCount = $cycle->risks()->count();
            if ($riskCount === 0) {
                return back()->withErrors(['transition' => 'At least one risk must be created before opening scoring.']);
            }
        }

        if ($cycle->state::$name === 'scoring' && $to === 'in_review') {
            $incomplete = $cycle->risks()->where(function ($q): void {
                $q->whereNull('inherent_score')->orWhereNull('residual_score');
            })->exists();
            if ($incomplete) {
                return back()->withErrors(['transition' => 'All risks must have inherent and residual scores before submitting for review.']);
            }
        }

        if ($to === 'signed_off' && empty($cycle->lead_assessor_id)) {
            return back()->withErrors(['transition' => 'A lead assessor must be assigned before signing off.']);
        }

        if ($to === 'scoring' && $cycle->state::$name === 'in_review' && ! empty($workshopNote)) {
            RiskWorkshopNote::create([
                'cycle_id' => $cycle->id,
                'recorded_by' => auth()->id(),
                'recorded_at' => now(),
                'attendees' => [],
                'notes' => $workshopNote,
            ]);
        }

        try {
            $this->service->transitionCycle($cycle, $to, auth()->id());
        } catch (TransitionNotFound $e) {
            return back()->withErrors(['transition' => 'This transition is not allowed from the current state.']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['transition' => $e->getMessage()]);
        }

        return redirect()->route('risk-assessments.show', $cycle->id)
            ->with('flash', ['type' => 'success', 'message' => 'Cycle transitioned successfully.']);
    }

    /** @return array<string, mixed> */
    private function formatCycle(RiskAssessmentCycle $cycle): array
    {
        return [
            'id' => $cycle->id,
            'reference' => $cycle->reference,
            'name' => $cycle->name,
            'lob' => $cycle->lob,
            'state' => $cycle->state::$name,
            'state_label' => $cycle->stateLabel(),
            'state_color' => $cycle->stateColor(),
            'cycle_year' => $cycle->cycle_year,
            'cycle_quarter' => $cycle->cycle_quarter,
            'methodology' => $cycle->methodology,
            'started_at' => $cycle->started_at?->format('Y-m-d'),
            'closed_at' => $cycle->closed_at?->format('Y-m-d'),
            'sla_due_date' => $cycle->sla_due_date?->format('Y-m-d'),
            'sla_days_remaining' => $cycle->slaDaysRemaining(),
            'summary' => $cycle->summary,
            'lead_assessor_id' => $cycle->lead_assessor_id,
            'created_at' => $cycle->created_at?->toIso8601String(),
            'updated_at' => $cycle->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function lobOptions(): array
    {
        return [
            ['value' => 'Retail', 'label' => 'Retail Banking'],
            ['value' => 'Corporate', 'label' => 'Corporate Banking'],
            ['value' => 'Treasury', 'label' => 'Treasury'],
            ['value' => 'Operations', 'label' => 'Operations'],
            ['value' => 'IT', 'label' => 'IT'],
            ['value' => 'Compliance', 'label' => 'Compliance'],
        ];
    }

    /** @return array<int, array{value: string, label: string, color: string}> */
    private function stateOptions(): array
    {
        return [
            ['value' => 'planning', 'label' => 'Planning', 'color' => 'gray'],
            ['value' => 'data_capture', 'label' => 'Data Capture', 'color' => 'blue'],
            ['value' => 'scoring', 'label' => 'Scoring', 'color' => 'orange'],
            ['value' => 'in_review', 'label' => 'In Review', 'color' => 'purple'],
            ['value' => 'signed_off', 'label' => 'Signed Off', 'color' => 'green'],
            ['value' => 'closed', 'label' => 'Closed', 'color' => 'gray'],
        ];
    }
}
