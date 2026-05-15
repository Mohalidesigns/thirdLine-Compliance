<?php

declare(strict_types=1);

namespace Modules\Controls\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Controls\Http\Requests\StoreIssueRequest;
use Modules\Controls\Http\Requests\UpdateIssueRequest;
use Modules\Controls\Models\Control;
use Modules\Controls\Models\Issue;
use Modules\Controls\Services\ControlsService;
use Modules\Library\Models\Obligation;
use Modules\Rcsa\Models\Risk;

class IssuesController extends Controller
{
    public function __construct(
        private readonly ControlsService $service,
    ) {}

    public function create(): InertiaResponse
    {
        $this->authorize('create', Issue::class);

        $controls = Control::query()
            ->where('status', 'active')
            ->orderBy('reference')
            ->get(['id', 'reference', 'title'])
            ->map(fn (Control $c) => ['id' => $c->id, 'reference' => $c->reference, 'title' => $c->title])
            ->toArray();

        $obligations = Obligation::query()
            ->orderBy('reference')
            ->get(['id', 'reference', 'title'])
            ->map(fn (Obligation $o) => ['id' => $o->id, 'reference' => $o->reference, 'title' => $o->title])
            ->toArray();

        $risks = Risk::query()
            ->orderBy('reference')
            ->get(['id', 'reference', 'title'])
            ->map(fn (Risk $r) => ['id' => $r->id, 'reference' => $r->reference, 'title' => $r->title])
            ->toArray();

        return Inertia::render('Issues/Create', [
            'severities' => [
                ['value' => 'low', 'label' => 'Low'],
                ['value' => 'medium', 'label' => 'Medium'],
                ['value' => 'high', 'label' => 'High'],
                ['value' => 'critical', 'label' => 'Critical'],
            ],
            'controls' => $controls,
            'obligations' => $obligations,
            'risks' => $risks,
        ]);
    }

    public function store(StoreIssueRequest $request): RedirectResponse
    {
        $this->authorize('create', Issue::class);

        $validated = $request->validated();

        $issue = Issue::create([
            'source_type' => 'manual',
            'source_id' => null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'severity' => $validated['severity'],
            'status' => 'open',
            'linked_control_id' => $validated['linked_control_id'] ?? null,
            'linked_obligation_id' => $validated['linked_obligation_id'] ?? null,
            'linked_risk_id' => $validated['linked_risk_id'] ?? null,
            'due_date' => $validated['due_at'] ?? null,
        ]);

        return redirect()->route('issues.show', $issue->id)
            ->with('flash', ['type' => 'success', 'message' => 'Issue raised.']);
    }

    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', Issue::class);

        $filters = $request->only(['status', 'severity', 'source_type']);
        $paginated = $this->service->paginatedIssues($filters);

        $issues = $paginated->through(fn (Issue $i) => [
            'id' => $i->id,
            'reference' => $i->reference,
            'title' => $i->title,
            'source_type' => $i->source_type,
            'severity' => $i->severity,
            'status' => $i->status,
            'due_date' => $i->due_date?->format('Y-m-d'),
            'owner_team' => $i->owner_team,
            'linked_control_id' => $i->linked_control_id,
            'created_at' => $i->created_at?->toIso8601String(),
        ]);

        $user = auth()->user();

        return Inertia::render('Issues/Index', [
            'issues' => $issues,
            'filters' => $filters,
            'can' => [
                'create' => $user?->can('create', Issue::class) ?? false,
            ],
            'statuses' => [
                ['value' => 'open', 'label' => 'Open'],
                ['value' => 'in_progress', 'label' => 'In Progress'],
                ['value' => 'resolved', 'label' => 'Resolved'],
                ['value' => 'closed', 'label' => 'Closed'],
                ['value' => 'dismissed', 'label' => 'Dismissed'],
            ],
            'severities' => [
                ['value' => 'low', 'label' => 'Low'],
                ['value' => 'medium', 'label' => 'Medium'],
                ['value' => 'high', 'label' => 'High'],
                ['value' => 'critical', 'label' => 'Critical'],
            ],
            'source_types' => [
                ['value' => 'control_test', 'label' => 'Control Test'],
                ['value' => 'manual', 'label' => 'Manual'],
                ['value' => 'ccm_rule', 'label' => 'CCM Rule'],
                ['value' => 'audit_finding', 'label' => 'Audit Finding'],
            ],
        ]);
    }

    public function show(int $id): InertiaResponse
    {
        $issue = $this->service->findIssue($id);
        $this->authorize('view', $issue);

        $issue->loadMissing('control');

        return Inertia::render('Issues/Show', [
            'issue' => [
                'id' => $issue->id,
                'reference' => $issue->reference,
                'source_type' => $issue->source_type,
                'source_id' => $issue->source_id,
                'title' => $issue->title,
                'description' => $issue->description,
                'severity' => $issue->severity,
                'status' => $issue->status,
                'due_date' => $issue->due_date?->format('Y-m-d'),
                'owner_team' => $issue->owner_team,
                'linked_control' => $issue->control ? [
                    'id' => $issue->control->id,
                    'reference' => $issue->control->reference,
                    'title' => $issue->control->title,
                ] : null,
                'resolution_notes' => $issue->resolution_notes,
                'resolved_at' => $issue->resolved_at?->toIso8601String(),
                'created_at' => $issue->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function update(UpdateIssueRequest $request, int $id): RedirectResponse
    {
        $issue = $this->service->findIssue($id);
        $this->authorize('update', $issue);

        $updateData = $request->validated();

        if (isset($updateData['status'])) {
            // Transitioning status requires the transition permission.
            $this->authorize('transition', $issue);
            $this->validateStatusTransition($issue->status, $updateData['status'], $updateData['resolution_notes'] ?? null);
        }

        if (isset($updateData['status']) && in_array($updateData['status'], ['resolved', 'closed']) && $issue->resolved_at === null) {
            $updateData['resolved_at'] = now();
        }

        $issue->update($updateData);

        return back()->with('flash', ['type' => 'success', 'message' => 'Issue updated.']);
    }

    /**
     * Enforce allowed status transitions and resolution_notes requirements.
     *
     * Allowed graph:
     *   open        → in_progress, dismissed
     *   in_progress → resolved, dismissed
     *   resolved    → closed, in_progress
     *   closed      → (terminal)
     *   dismissed   → (terminal)
     *
     * @throws HttpResponseException (422)
     */
    private function validateStatusTransition(string $current, string $requested, ?string $resolutionNotes): void
    {
        $allowed = [
            'open' => ['in_progress', 'dismissed'],
            'in_progress' => ['resolved', 'dismissed'],
            'resolved' => ['closed', 'in_progress'],
            'closed' => [],
            'dismissed' => [],
        ];

        $allowedNext = $allowed[$current] ?? [];

        if (! in_array($requested, $allowedNext, true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot transition issue from '{$current}' to '{$requested}'.",
            ]);
        }

        if (in_array($requested, ['resolved', 'closed'], true) && empty(trim((string) $resolutionNotes))) {
            throw ValidationException::withMessages([
                'resolution_notes' => "Resolution notes are required when transitioning to '{$requested}'.",
            ]);
        }
    }
}
