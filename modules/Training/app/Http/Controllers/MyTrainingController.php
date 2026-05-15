<?php

declare(strict_types=1);

namespace Modules\Training\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Training\Models\TrainingEnrollment;
use Modules\Training\Services\TrainingService;

class MyTrainingController extends Controller
{
    public function __construct(
        private readonly TrainingService $service,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $this->authorize('viewAny', TrainingEnrollment::class);

        $filters = $request->only(['status', 'category']);

        $query = TrainingEnrollment::with('training')
            ->where('user_id', $user->id)
            ->orderBy('due_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category'])) {
            $query->whereHas('training', function ($q) use ($filters): void {
                $q->where('category', $filters['category']);
            });
        }

        $enrollments = $query->get()->map(fn (TrainingEnrollment $e) => $this->formatEnrollmentSummary($e));

        $all = TrainingEnrollment::where('user_id', $user->id)->get();

        $stats = [
            'total' => $all->count(),
            'completed' => $all->where('status', 'completed')->count(),
            'in_progress' => $all->where('status', 'in_progress')->count(),
            'overdue' => $all->where('status', 'overdue')->count(),
            'completion_rate' => $all->count() > 0
                ? round($all->where('status', 'completed')->count() / $all->count(), 4)
                : 0.0,
        ];

        return Inertia::render('My/Training/Index', [
            'enrollments' => $enrollments,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    public function show(int $enrollmentId): InertiaResponse
    {
        $enrollment = TrainingEnrollment::with('training')->findOrFail($enrollmentId);
        $this->authorize('view', $enrollment);

        $user = request()->user();

        return Inertia::render('My/Training/Show', [
            'enrollment' => array_merge($this->formatEnrollmentSummary($enrollment), [
                'training' => [
                    'id' => $enrollment->training->id,
                    'code' => $enrollment->training->code,
                    'title' => $enrollment->training->title,
                    'description' => $enrollment->training->description,
                    'category' => $enrollment->training->category,
                    'is_mandatory' => $enrollment->training->is_mandatory,
                    'sla_days' => $enrollment->training->sla_days,
                    'source' => $enrollment->training->source,
                    'source_url' => $enrollment->training->source_url,
                ],
            ]),
            'can' => [
                'start' => $user->can('start', $enrollment),
                'complete' => $user->can('complete', $enrollment),
            ],
        ]);
    }

    public function start(int $enrollmentId): RedirectResponse
    {
        $enrollment = TrainingEnrollment::findOrFail($enrollmentId);
        $this->authorize('start', $enrollment);

        $this->service->markStarted($enrollment);

        return redirect()->route('my.training.show', $enrollmentId)
            ->with('flash', ['type' => 'success', 'message' => 'Training started.']);
    }

    public function complete(int $enrollmentId, Request $request): RedirectResponse
    {
        $enrollment = TrainingEnrollment::findOrFail($enrollmentId);
        $this->authorize('complete', $enrollment);

        $validated = $request->validate([
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $this->service->markCompleted($enrollment, $validated['score'] ?? null, $request->user()->id);

        return redirect()->route('my.training.index')
            ->with('flash', ['type' => 'success', 'message' => 'Training marked as completed.']);
    }

    /** @return array<string, mixed> */
    private function formatEnrollmentSummary(TrainingEnrollment $e): array
    {
        return [
            'id' => $e->id,
            'training' => [
                'id' => $e->training->id,
                'code' => $e->training->code,
                'title' => $e->training->title,
                'category' => $e->training->category,
                'is_mandatory' => $e->training->is_mandatory,
            ],
            'enrolled_at' => $e->enrolled_at?->toIso8601String(),
            'due_at' => $e->due_at?->toIso8601String(),
            'started_at' => $e->started_at?->toIso8601String(),
            'completed_at' => $e->completed_at?->toIso8601String(),
            'score' => $e->score,
            'status' => $e->status,
            'days_until_due' => $e->daysUntilDue(),
        ];
    }
}
