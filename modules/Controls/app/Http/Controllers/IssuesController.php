<?php

declare(strict_types=1);

namespace Modules\Controls\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Controls\Http\Requests\UpdateIssueRequest;
use Modules\Controls\Models\Issue;
use Modules\Controls\Services\ControlsService;

class IssuesController extends Controller
{
    public function __construct(
        private readonly ControlsService $service,
    ) {}

    public function index(Request $request): InertiaResponse
    {
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

        return Inertia::render('Issues/Index', [
            'issues' => $issues,
            'filters' => $filters,
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

        $updateData = $request->validated();

        if (isset($updateData['status']) && in_array($updateData['status'], ['resolved', 'closed']) && $issue->resolved_at === null) {
            $updateData['resolved_at'] = now();
        }

        $issue->update($updateData);

        return back()->with('flash', ['type' => 'success', 'message' => 'Issue updated.']);
    }
}
