<?php

declare(strict_types=1);

namespace Modules\Controls\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Controls\Models\Control;
use Modules\Controls\Models\ControlTest;
use Modules\Controls\Services\ControlsService;

class TestsController extends Controller
{
    public function __construct(
        private readonly ControlsService $service,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $filters = $request->only(['control', 'outcome', 'date_from', 'date_to']);

        $controlId = isset($filters['control']) ? (int) $filters['control'] : 0;
        $paginated = $controlId > 0
            ? $this->service->paginatedTests($controlId, $filters)
            : ControlTest::query()
                ->with(['control', 'tester'])
                ->orderByDesc('tested_at')
                ->paginate(25)
                ->withQueryString();

        $tests = $paginated->through(fn (ControlTest $t) => [
            'id' => $t->id,
            'control' => [
                'id' => $t->control_id,
                'reference' => $t->control?->reference,
                'title' => $t->control?->title,
            ],
            'tested_at' => $t->tested_at?->toIso8601String(),
            'outcome' => $t->outcome,
            'sample_size' => $t->sample_size,
            'findings' => $t->findings,
            'tested_by_name' => $t->tester?->name,
        ]);

        $controls = Control::query()
            ->select(['id', 'reference', 'title'])
            ->orderBy('reference')
            ->get()
            ->map(fn (Control $c) => ['value' => $c->id, 'label' => "{$c->reference} — {$c->title}"])
            ->toArray();

        return Inertia::render('Tests/Index', [
            'tests' => $tests,
            'filters' => $filters,
            'controls' => $controls,
            'outcomes' => [
                ['value' => 'passed', 'label' => 'Passed'],
                ['value' => 'partial', 'label' => 'Partial'],
                ['value' => 'failed', 'label' => 'Failed'],
                ['value' => 'not_applicable', 'label' => 'N/A'],
            ],
        ]);
    }

    public function show(int $id): InertiaResponse
    {
        $test = $this->service->findTest($id);
        $test->loadMissing(['control', 'tester']);

        return Inertia::render('Tests/Show', [
            'test' => [
                'id' => $test->id,
                'control' => [
                    'id' => $test->control?->id,
                    'reference' => $test->control?->reference,
                    'title' => $test->control?->title,
                ],
                'tested_by_name' => $test->tester?->name,
                'tested_at' => $test->tested_at?->toIso8601String(),
                'period_start' => $test->period_start?->format('Y-m-d'),
                'period_end' => $test->period_end?->format('Y-m-d'),
                'sample_size' => $test->sample_size,
                'population_size' => $test->population_size,
                'confidence_level' => $test->confidence_level,
                'outcome' => $test->outcome,
                'evidence_url' => $test->evidence_url,
                'findings' => $test->findings,
                'created_at' => $test->created_at?->toIso8601String(),
            ],
        ]);
    }
}
