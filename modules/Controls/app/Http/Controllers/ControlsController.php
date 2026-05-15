<?php

declare(strict_types=1);

namespace Modules\Controls\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Controls\Http\Requests\RecordTestRequest;
use Modules\Controls\Http\Requests\StoreControlRequest;
use Modules\Controls\Http\Requests\UpdateControlRequest;
use Modules\Controls\Models\Control;
use Modules\Controls\Services\ControlsService;
use Modules\Controls\Services\SampleSizeCalculator;

class ControlsController extends Controller
{
    public function __construct(
        private readonly ControlsService $service,
        private readonly SampleSizeCalculator $calculator,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $filters = $request->only(['search', 'type', 'frequency', 'status', 'owner', 'due_soon']);
        $paginated = $this->service->paginatedControls($filters);

        $controls = $paginated->through(fn (Control $c) => [
            'id' => $c->id,
            'reference' => $c->reference,
            'title' => $c->title,
            'control_type' => $c->control_type,
            'nature' => $c->nature,
            'frequency' => $c->frequency,
            'frequency_label' => $c->frequencyLabel(),
            'owner_team' => $c->owner_team,
            'status' => $c->status,
            'last_tested_at' => $c->last_tested_at?->toIso8601String(),
            'next_test_due' => $c->next_test_due?->format('Y-m-d'),
            'days_until_due' => $c->daysUntilDue(),
        ]);

        return Inertia::render('Controls/Index', [
            'controls' => $controls,
            'filters' => $filters,
            'types' => $this->typeOptions(),
            'frequencies' => $this->frequencyOptions(),
            'statuses' => $this->statusOptions(),
            'due_count' => $this->service->dueSoonCount(),
            'overdue_count' => $this->service->overdueCount(),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Controls/Create', [
            'types' => $this->typeOptions(),
            'natures' => $this->natureOptions(),
            'frequencies' => $this->frequencyOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(StoreControlRequest $request): RedirectResponse
    {
        $control = $this->service->create($request->validated());

        return redirect()->route('controls.show', $control->id)
            ->with('flash', ['type' => 'success', 'message' => 'Control created.']);
    }

    public function show(int $id): InertiaResponse
    {
        $control = $this->service->find($id);
        $recentTests = $control->tests()
            ->with('tester')
            ->orderByDesc('tested_at')
            ->limit(10)
            ->get();

        $defaultPop = 200;
        $suggestedSample = $this->calculator->calculate($defaultPop);

        return Inertia::render('Controls/Show', [
            'control' => array_merge($this->formatControl($control), [
                'linked_obligations' => [],
                'linked_risks' => [],
            ]),
            'recent_tests' => $recentTests->map(fn ($t) => [
                'id' => $t->id,
                'tested_at' => $t->tested_at?->toIso8601String(),
                'outcome' => $t->outcome,
                'findings' => $t->findings,
                'sample_size' => $t->sample_size,
                'population_size' => $t->population_size,
                'tested_by_name' => $t->tester?->name,
            ])->toArray(),
            'related_issues_count' => $control->issues()->count(),
            'next_test_due' => $control->next_test_due?->format('Y-m-d'),
            'sample_size_suggestion' => $suggestedSample,
            'can' => [
                'edit' => true,
                'delete' => $control->status === 'draft',
                'test' => $control->status === 'active',
            ],
        ]);
    }

    public function edit(int $id): InertiaResponse
    {
        $control = $this->service->find($id);

        return Inertia::render('Controls/Edit', [
            'control' => $this->formatControl($control),
            'types' => $this->typeOptions(),
            'natures' => $this->natureOptions(),
            'frequencies' => $this->frequencyOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(UpdateControlRequest $request, int $id): RedirectResponse
    {
        $control = $this->service->find($id);
        $this->service->update($control, $request->validated());

        return redirect()->route('controls.show', $control->id)
            ->with('flash', ['type' => 'success', 'message' => 'Control updated.']);
    }

    public function destroy(int $id): RedirectResponse
    {
        $control = $this->service->find($id);

        if ($control->status !== 'draft') {
            abort(403, 'Only draft controls can be deleted.');
        }

        $control->delete();

        return redirect()->route('controls.index')
            ->with('flash', ['type' => 'success', 'message' => 'Control deleted.']);
    }

    public function test(int $id): InertiaResponse
    {
        $control = $this->service->find($id);

        $population = 200;
        $suggested = $this->calculator->calculate($population);

        return Inertia::render('Controls/Test', [
            'control' => [
                'id' => $control->id,
                'reference' => $control->reference,
                'title' => $control->title,
                'frequency' => $control->frequency,
                'last_tested_at' => $control->last_tested_at?->toIso8601String(),
            ],
            'suggested_sample_size' => $suggested,
        ]);
    }

    public function recordTest(RecordTestRequest $request, int $id): RedirectResponse
    {
        $test = $this->service->recordTest($id, $request->validated(), auth()->id());

        return redirect()->route('controls.show', $id)
            ->with('flash', ['type' => 'success', 'message' => 'Test recorded.'.($test->outcome === 'failed' ? ' Issue automatically opened.' : '')]);
    }

    /** @return array<string, mixed> */
    private function formatControl(Control $control): array
    {
        return [
            'id' => $control->id,
            'reference' => $control->reference,
            'title' => $control->title,
            'description' => $control->description,
            'control_type' => $control->control_type,
            'nature' => $control->nature,
            'frequency' => $control->frequency,
            'frequency_label' => $control->frequencyLabel(),
            'owner_team' => $control->owner_team,
            'linked_obligation_ids' => $control->linked_obligation_ids,
            'linked_risk_ids' => $control->linked_risk_ids,
            'status' => $control->status,
            'last_tested_at' => $control->last_tested_at?->toIso8601String(),
            'next_test_due' => $control->next_test_due?->format('Y-m-d'),
            'days_until_due' => $control->daysUntilDue(),
            'created_at' => $control->created_at?->toIso8601String(),
            'updated_at' => $control->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function typeOptions(): array
    {
        return [
            ['value' => 'preventive', 'label' => 'Preventive'],
            ['value' => 'detective', 'label' => 'Detective'],
            ['value' => 'corrective', 'label' => 'Corrective'],
            ['value' => 'compensating', 'label' => 'Compensating'],
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function natureOptions(): array
    {
        return [
            ['value' => 'manual', 'label' => 'Manual'],
            ['value' => 'automated', 'label' => 'Automated'],
            ['value' => 'hybrid', 'label' => 'Hybrid'],
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function frequencyOptions(): array
    {
        return [
            ['value' => 'continuous', 'label' => 'Continuous'],
            ['value' => 'daily', 'label' => 'Daily'],
            ['value' => 'weekly', 'label' => 'Weekly'],
            ['value' => 'monthly', 'label' => 'Monthly'],
            ['value' => 'quarterly', 'label' => 'Quarterly'],
            ['value' => 'semiannual', 'label' => 'Semi-Annual'],
            ['value' => 'annual', 'label' => 'Annual'],
            ['value' => 'event_driven', 'label' => 'Event-Driven'],
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function statusOptions(): array
    {
        return [
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'deprecated', 'label' => 'Deprecated'],
        ];
    }
}
