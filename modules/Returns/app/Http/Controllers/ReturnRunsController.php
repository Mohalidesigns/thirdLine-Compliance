<?php

declare(strict_types=1);

namespace Modules\Returns\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Returns\Models\ReturnApproval;
use Modules\Returns\Models\ReturnDefinition;
use Modules\Returns\Models\ReturnRun;
use Modules\Returns\Services\ReturnsService;

class ReturnRunsController extends Controller
{
    public function __construct(
        private readonly ReturnsService $service,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', ReturnRun::class);

        $filters = $request->only(['search', 'regulator', 'frequency', 'status']);

        $query = ReturnRun::query()
            ->with('definition:id,code,title,regulator,frequency')
            ->with(['maker:id,name', 'checker:id,name', 'approver:id,name'])
            ->orderBy('due_at');

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->whereHas('definition', function ($q) use ($term, $likeOp): void {
                $q->where('title', $likeOp, "%{$term}%")
                    ->orWhere('code', $likeOp, "%{$term}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['regulator'])) {
            $query->whereHas('definition', fn ($q) => $q->where('regulator', $filters['regulator']));
        }

        if (! empty($filters['frequency'])) {
            $query->whereHas('definition', fn ($q) => $q->where('frequency', $filters['frequency']));
        }

        $paginated = $query->paginate(25)->withQueryString();

        $runs = $paginated->through(fn (ReturnRun $r) => [
            'id' => $r->id,
            'code' => $r->definition->code,
            'title' => $r->definition->title,
            'regulator' => $r->definition->regulator,
            'regulator_label' => $r->definition->regulatorLabel(),
            'period_label' => $r->period_label,
            'due_at' => $r->due_at->toIso8601String(),
            'days_until_due' => $r->daysUntilDue(),
            'status' => $r->status,
            'status_label' => $r->statusLabel(),
            'status_color' => $r->statusColor(),
            'maker_name' => $r->maker?->name,
            'checker_name' => $r->checker?->name,
            'approver_name' => $r->approver?->name,
            'submitted_at' => $r->submitted_at?->toIso8601String(),
            'acknowledged_at' => $r->acknowledged_at?->toIso8601String(),
            'submission_reference' => $r->submission_reference,
            'is_overdue' => $r->isOverdue(),
        ]);

        return Inertia::render('Returns/Runs/Index', [
            'runs' => $runs,
            'filters' => $filters,
            'regulator_options' => $this->regulatorOptions(),
            'frequency_options' => $this->frequencyOptions(),
            'status_options' => $this->statusOptions(),
        ]);
    }

    public function show(ReturnRun $run): InertiaResponse
    {
        $this->authorize('view', $run);

        $run->load([
            'definition',
            'maker:id,name',
            'checker:id,name',
            'approver:id,name',
            'approvals.actor:id,name',
        ]);

        $user = auth()->user();
        $availableActions = $this->computeAvailableActions($run, $user);

        return Inertia::render('Returns/Runs/Show', [
            'run' => [
                'id' => $run->id,
                'definition' => [
                    'id' => $run->definition->id,
                    'code' => $run->definition->code,
                    'title' => $run->definition->title,
                    'regulator' => $run->definition->regulator,
                    'regulator_label' => $run->definition->regulatorLabel(),
                    'submission_channel' => $run->definition->submission_channel,
                    'frequency' => $run->definition->frequency,
                    'evidence_required' => $run->definition->evidence_required,
                    'legal_basis' => $run->definition->legal_basis,
                    'acts' => $run->definition->acts,
                ],
                'period_label' => $run->period_label,
                'period_start' => $run->period_start->toDateString(),
                'period_end' => $run->period_end->toDateString(),
                'due_at' => $run->due_at->toIso8601String(),
                'days_until_due' => $run->daysUntilDue(),
                'status' => $run->status,
                'status_label' => $run->statusLabel(),
                'status_color' => $run->statusColor(),
                'maker' => $run->maker ? ['id' => $run->maker->id, 'name' => $run->maker->name] : null,
                'checker' => $run->checker ? ['id' => $run->checker->id, 'name' => $run->checker->name] : null,
                'approver' => $run->approver ? ['id' => $run->approver->id, 'name' => $run->approver->name] : null,
                'payload_path' => $run->payload_path,
                'submission_reference' => $run->submission_reference,
                'submitted_at' => $run->submitted_at?->toIso8601String(),
                'acknowledged_at' => $run->acknowledged_at?->toIso8601String(),
                'acknowledgement_path' => $run->acknowledgement_path,
                'rejection_reason' => $run->rejection_reason,
                'notes' => $run->notes,
            ],
            'approvals' => $run->approvals->map(fn (ReturnApproval $a) => [
                'id' => $a->id,
                'step' => $a->step,
                'step_label' => $a->stepLabel(),
                'actor_name' => $a->actor?->name,
                'decision' => $a->decision,
                'notes' => $a->notes,
                'acted_at' => $a->acted_at->toIso8601String(),
            ])->toArray(),
            'available_actions' => $availableActions,
            'can' => [
                'submit' => $user?->can('submitForReview', $run) ?? false,
                'approve' => $user?->can('approveAsChecker', $run) ?? false,
                'sign_off' => $user?->can('signOff', $run) ?? false,
                'acknowledge' => $user?->can('recordAcknowledgement', $run) ?? false,
            ],
        ]);
    }

    public function submitForReview(Request $request, ReturnRun $run): RedirectResponse
    {
        $this->authorize('submitForReview', $run);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        $this->service->submitForReview($run, auth()->id(), $validated['notes'] ?? null);

        return back()->with('flash', ['type' => 'success', 'message' => 'Return submitted for review.']);
    }

    public function approveAsChecker(Request $request, ReturnRun $run): RedirectResponse
    {
        $this->authorize('approveAsChecker', $run);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->service->approve($run, auth()->id(), $validated['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['approval' => $e->getMessage()]);
        }

        return back()->with('flash', ['type' => 'success', 'message' => 'Return approved at checker step.']);
    }

    public function signOff(Request $request, ReturnRun $run): RedirectResponse
    {
        $this->authorize('signOff', $run);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
            'manual_reference' => 'nullable|string|max:200',
        ]);

        try {
            $this->service->signOffAndSubmit(
                $run,
                auth()->id(),
                $validated['notes'] ?? null,
                $validated['manual_reference'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['sign_off' => $e->getMessage()]);
        }

        return back()->with('flash', ['type' => 'success', 'message' => 'Return signed off and submitted.']);
    }

    public function recordAcknowledgement(Request $request, ReturnRun $run): RedirectResponse
    {
        $this->authorize('recordAcknowledgement', $run);

        $validated = $request->validate([
            'acknowledgement_path' => 'required|string|max:500',
        ]);

        $this->service->recordAcknowledgement($run, $validated['acknowledgement_path'], auth()->id());

        return back()->with('flash', ['type' => 'success', 'message' => 'Acknowledgement recorded.']);
    }

    // ---------------------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------------------

    /**
     * Compute the list of available actions for the current user and run state.
     * This is what the frontend reads to show action buttons.
     *
     * @return list<'submit'|'approve'|'sign_off'|'acknowledge'>
     */
    private function computeAvailableActions(ReturnRun $run, mixed $user): array
    {
        if ($user === null) {
            return [];
        }

        $actions = [];

        if (in_array($run->status, ['scheduled', 'in_progress'], true)
            && $user->can('submitForReview', $run)) {
            $actions[] = 'submit';
        }

        if ($run->status === 'in_progress'
            && $user->can('approveAsChecker', $run)) {
            $actions[] = 'approve';
        }

        if ($run->status === 'in_progress'
            && $run->checker_id !== null
            && $user->can('signOff', $run)) {
            $actions[] = 'sign_off';
        }

        if ($run->status === 'submitted_pending_ack'
            && $user->can('recordAcknowledgement', $run)) {
            $actions[] = 'acknowledge';
        }

        return $actions;
    }

    /** @return array<int, array{value: string, label: string}> */
    private function regulatorOptions(): array
    {
        return collect(ReturnDefinition::regulatorLabels())
            ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->toArray();
    }

    /** @return array<int, array{value: string, label: string}> */
    private function frequencyOptions(): array
    {
        return collect(ReturnDefinition::frequencyLabels())
            ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->toArray();
    }

    /** @return array<int, array{value: string, label: string}> */
    private function statusOptions(): array
    {
        return [
            ['value' => 'scheduled', 'label' => 'Scheduled'],
            ['value' => 'in_progress', 'label' => 'In Progress'],
            ['value' => 'submitted_pending_ack', 'label' => 'Pending Acknowledgement'],
            ['value' => 'acknowledged', 'label' => 'Acknowledged'],
            ['value' => 'late', 'label' => 'Late'],
            ['value' => 'rejected', 'label' => 'Rejected'],
        ];
    }
}
