<?php

declare(strict_types=1);

namespace Modules\Incident\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Controls\Models\Control;
use Modules\Incident\Http\Requests\RecordLossRequest;
use Modules\Incident\Http\Requests\RecordNotificationRequest;
use Modules\Incident\Http\Requests\StoreEvidenceRequest;
use Modules\Incident\Http\Requests\StoreIncidentActionRequest;
use Modules\Incident\Http\Requests\StoreIncidentRequest;
use Modules\Incident\Http\Requests\TransitionIncidentRequest;
use Modules\Incident\Http\Requests\UpdateIncidentRequest;
use Modules\Incident\Models\Incident;
use Modules\Incident\Models\IncidentAction;
use Modules\Incident\Models\IncidentNotification;
use Modules\Incident\Services\IncidentService;
use Modules\Policy\Models\Policy;
use Modules\Rcsa\Models\Risk;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

class IncidentsController extends Controller
{
    public function __construct(
        private readonly IncidentService $service,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', Incident::class);

        $filters = $request->only(['search', 'category', 'severity', 'status', 'basel_category', 'is_data_breach']);

        // Normalise is_data_breach to bool|null
        if (array_key_exists('is_data_breach', $filters)) {
            $filters['is_data_breach'] = match ($filters['is_data_breach']) {
                '1', 'true', true => true,
                '0', 'false', false => false,
                default => null,
            };
        }

        $paginated = $this->service->paginatedList($filters);

        $incidents = $paginated->through(fn (Incident $i) => [
            'id' => $i->id,
            'code' => $i->code,
            'title' => $i->title,
            'category' => $i->category,
            'severity' => $i->severity,
            'status' => $i->status::$name,
            'status_label' => $i->statusLabel(),
            'status_color' => $i->statusColor(),
            'detected_at' => $i->detected_at->toIso8601String(),
            'occurred_at' => $i->occurred_at?->toIso8601String(),
            'is_data_breach' => $i->is_data_breach,
            'is_cyber_incident' => $i->is_cyber_incident,
            'affects_customers' => $i->affects_customers,
            'financial_impact' => $i->financial_impact !== null
                ? number_format((float) $i->financial_impact, 2)
                : null,
            'currency' => $i->currency,
            'notifications_pending' => (int) ($i->notifications_pending ?? 0),
            'notifications_overdue' => (int) ($i->notifications_overdue ?? 0),
            'open_actions' => (int) ($i->open_actions ?? 0),
            'days_open' => $i->daysOpen(),
            'assigned_to' => $i->assignedTo ? ['id' => $i->assignedTo->id, 'name' => $i->assignedTo->name] : null,
            'reported_by' => $i->reportedBy ? ['id' => $i->reportedBy->id, 'name' => $i->reportedBy->name] : null,
        ]);

        return Inertia::render('Incidents/Index', [
            'incidents' => $incidents,
            'stats' => $this->service->stats(),
            'filters' => $filters,
            'category_options' => $this->categoryOptions(),
            'severity_options' => $this->severityOptions(),
            'status_options' => $this->statusOptions(),
            'basel_options' => $this->baselOptions(),
            'can' => [
                'create' => auth()->user()?->can('create', Incident::class),
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', Incident::class);

        return Inertia::render('Incidents/Create', [
            'category_options' => $this->categoryOptions(),
            'severity_options' => $this->severityOptions(),
            'basel_options' => $this->baselOptions(),
            'risks_options' => $this->risksOptions(),
            'controls_options' => $this->controlsOptions(),
            'policies_options' => $this->policiesOptions(),
            'users_options' => $this->usersOptions(),
        ]);
    }

    public function store(StoreIncidentRequest $request): RedirectResponse
    {
        $this->authorize('create', Incident::class);

        $incident = $this->service->create($request->validated(), auth()->id());

        return redirect()->route('incidents.show', $incident->id)
            ->with('flash', ['type' => 'success', 'message' => 'Incident reported.']);
    }

    public function show(int $id): InertiaResponse
    {
        $incident = $this->service->find($id);
        $this->authorize('view', $incident);

        $incident->load([
            'actions.owner:id,name',
            'actions.completedBy:id,name',
            'notifications',
            'evidence.uploader:id,name',
            'lossEvent',
            'closedBy:id,name',
        ]);

        $user = auth()->user();

        // Load linked entities for display
        $linkedRisk = $incident->linked_risk_id
            ? Risk::withoutGlobalScopes()->select(['id', 'reference', 'title'])->find($incident->linked_risk_id)
            : null;

        $linkedControl = $incident->linked_control_id
            ? Control::withoutGlobalScopes()->select(['id', 'reference', 'title'])->find($incident->linked_control_id)
            : null;

        $linkedPolicy = $incident->linked_policy_id
            ? Policy::withoutGlobalScopes()->select(['id', 'reference', 'title'])->find($incident->linked_policy_id)
            : null;

        $loss = $incident->lossEvent;

        return Inertia::render('Incidents/Show', [
            'incident' => [
                'id' => $incident->id,
                'code' => $incident->code,
                'title' => $incident->title,
                'description' => $incident->description,
                'category' => $incident->category,
                'severity' => $incident->severity,
                'status' => $incident->status::$name,
                'status_label' => $incident->statusLabel(),
                'status_color' => $incident->statusColor(),
                'occurred_at' => $incident->occurred_at?->toIso8601String(),
                'detected_at' => $incident->detected_at->toIso8601String(),
                'closed_at' => $incident->closed_at?->toIso8601String(),
                'closed_by_name' => $incident->closedBy?->name,
                'basel_category' => $incident->basel_category,
                'financial_impact' => $incident->financial_impact !== null
                    ? number_format((float) $incident->financial_impact, 2)
                    : null,
                'currency' => $incident->currency,
                'is_data_breach' => $incident->is_data_breach,
                'is_cyber_incident' => $incident->is_cyber_incident,
                'affects_customers' => $incident->affects_customers,
                'affected_customer_count' => $incident->affected_customer_count,
                'root_cause' => $incident->root_cause,
                'lessons_learned' => $incident->lessons_learned,
                'reported_by_name' => $incident->reportedBy?->name,
                'assigned_to_name' => $incident->assignedTo?->name,
                'reporter_external_source' => $incident->reporter_external_source,
                'linked_risk' => $linkedRisk ? [
                    'id' => $linkedRisk->id,
                    'code' => $linkedRisk->reference,
                    'title' => $linkedRisk->title,
                ] : null,
                'linked_control' => $linkedControl ? [
                    'id' => $linkedControl->id,
                    'code' => $linkedControl->reference,
                    'title' => $linkedControl->title,
                ] : null,
                'linked_policy' => $linkedPolicy ? [
                    'id' => $linkedPolicy->id,
                    'code' => $linkedPolicy->reference,
                    'title' => $linkedPolicy->title,
                ] : null,
                'created_at' => $incident->created_at?->toIso8601String(),
                'updated_at' => $incident->updated_at?->toIso8601String(),
            ],
            'actions' => $incident->actions->map(fn ($a) => [
                'id' => $a->id,
                'type' => $a->type,
                'title' => $a->title,
                'description' => $a->description,
                'owner_name' => $a->owner?->name,
                'due_at' => $a->due_at?->format('Y-m-d'),
                'completed_at' => $a->completed_at?->toIso8601String(),
                'completed_by_name' => $a->completedBy?->name,
                'evidence_notes' => $a->evidence_notes,
                'is_overdue' => $a->isOverdue(),
            ])->toArray(),
            'notifications' => $incident->notifications->map(fn ($n) => [
                'id' => $n->id,
                'regulator' => $n->regulator,
                'regulator_label' => $n->regulatorLabel(),
                'deadline_at' => $n->deadline_at->toIso8601String(),
                'notified_at' => $n->notified_at?->toIso8601String(),
                'notification_reference' => $n->notification_reference,
                'status' => $n->status,
                'status_color' => $n->statusColor(),
                'hours_remaining' => $n->hoursRemaining(),
                'is_overdue' => $n->isOverdue(),
            ])->toArray(),
            'evidence' => $incident->evidence->map(fn ($e) => [
                'id' => $e->id,
                'type' => $e->type,
                'type_label' => $e->typeLabel(),
                'title' => $e->title,
                'description' => $e->description,
                'file_path' => $e->file_path,
                'uploaded_by_name' => $e->uploader?->name,
                'created_at' => $e->created_at?->toIso8601String(),
            ])->toArray(),
            'loss_event' => $loss ? [
                'gross_loss' => number_format((float) $loss->gross_loss, 2),
                'recovery_amount' => number_format((float) $loss->recovery_amount, 2),
                'net_loss' => $loss->netLoss(),
                'currency' => $loss->net_loss_currency,
                'event_date' => $loss->event_date->format('Y-m-d'),
                'recognized_date' => $loss->recognized_date->format('Y-m-d'),
            ] : null,
            'available_transitions' => $incident->allowedTransitions(),
            'can' => [
                'update' => $user?->can('update', $incident),
                'delete' => $user?->can('delete', $incident),
                'transition' => $user?->can('update', $incident),
                'notify' => $user?->can('notify', $incident),
                'attach_evidence' => $user?->can('attachEvidence', $incident),
                'close' => $user?->can('close', $incident),
                'record_loss' => $user?->can('recordLoss', $incident),
            ],
        ]);
    }

    public function edit(int $id): InertiaResponse
    {
        $incident = $this->service->find($id);
        $this->authorize('update', $incident);

        return Inertia::render('Incidents/Edit', [
            'incident' => $this->formatIncidentForEdit($incident),
            'category_options' => $this->categoryOptions(),
            'severity_options' => $this->severityOptions(),
            'basel_options' => $this->baselOptions(),
            'risks_options' => $this->risksOptions(),
            'controls_options' => $this->controlsOptions(),
            'policies_options' => $this->policiesOptions(),
            'users_options' => $this->usersOptions(),
        ]);
    }

    public function update(UpdateIncidentRequest $request, int $id): RedirectResponse
    {
        $incident = $this->service->find($id);
        $this->authorize('update', $incident);

        $incident->update($request->validated());

        return redirect()->route('incidents.show', $incident->id)
            ->with('flash', ['type' => 'success', 'message' => 'Incident updated.']);
    }

    public function destroy(int $id): RedirectResponse
    {
        $incident = $this->service->find($id);
        $this->authorize('delete', $incident);

        $incident->delete();

        return redirect()->route('incidents.index')
            ->with('flash', ['type' => 'success', 'message' => 'Incident deleted.']);
    }

    public function transition(TransitionIncidentRequest $request, int $incident): RedirectResponse
    {
        $incident = $this->service->find($incident);
        $this->authorize('update', $incident);

        $to = $request->validated('to');

        try {
            $this->service->transition($incident, $to, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['transition' => $e->getMessage()]);
        } catch (TransitionNotFound $e) {
            return back()->withErrors(['transition' => 'This transition is not allowed from the current state.']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['transition' => $e->getMessage()]);
        }

        return redirect()->route('incidents.show', $incident->id)
            ->with('flash', ['type' => 'success', 'message' => 'Incident status updated.']);
    }

    public function attachEvidence(StoreEvidenceRequest $request, int $incident): RedirectResponse
    {
        $incident = $this->service->find($incident);
        $this->authorize('attachEvidence', $incident);

        $this->service->attachEvidence($incident, $request->validated(), auth()->id());

        return back()->with('flash', ['type' => 'success', 'message' => 'Evidence attached.']);
    }

    public function storeAction(StoreIncidentActionRequest $request, int $incident): RedirectResponse
    {
        $incident = $this->service->find($incident);
        $this->authorize('update', $incident);

        IncidentAction::create(array_merge(
            $request->validated(),
            ['incident_id' => $incident->id],
        ));

        return back()->with('flash', ['type' => 'success', 'message' => 'CAPA action added.']);
    }

    public function completeAction(Request $request, int $incident, int $action): RedirectResponse
    {
        $incident = $this->service->find($incident);
        $this->authorize('update', $incident);

        $request->validate(['evidence_notes' => 'nullable|string']);

        $actionModel = IncidentAction::where('incident_id', $incident->id)->findOrFail($action);

        if ($actionModel->completed_at !== null) {
            return back()->withErrors(['action' => 'Action already completed.']);
        }

        $actionModel->update([
            'completed_at' => now(),
            'completed_by' => auth()->id(),
            'evidence_notes' => $request->input('evidence_notes'),
        ]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Action marked completed.']);
    }

    public function recordNotification(
        RecordNotificationRequest $request,
        int $incident,
        int $notification,
    ): RedirectResponse {
        $incident = $this->service->find($incident);
        $this->authorize('notify', $incident);

        $notificationModel = IncidentNotification::where('incident_id', $incident->id)
            ->findOrFail($notification);

        $this->service->recordNotification(
            $notificationModel,
            $request->validated('reference'),
            auth()->id(),
        );

        return back()->with('flash', ['type' => 'success', 'message' => 'Notification recorded.']);
    }

    public function recordLoss(RecordLossRequest $request, int $incident): RedirectResponse
    {
        $incident = $this->service->find($incident);
        $this->authorize('recordLoss', $incident);

        $this->service->recordLoss($incident, $request->validated());

        return back()->with('flash', ['type' => 'success', 'message' => 'Loss event recorded.']);
    }

    // ---------------------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------------------

    /** @return array<string, mixed> */
    private function formatIncidentForEdit(Incident $incident): array
    {
        return [
            'id' => $incident->id,
            'code' => $incident->code,
            'title' => $incident->title,
            'description' => $incident->description,
            'category' => $incident->category,
            'severity' => $incident->severity,
            'status' => $incident->status::$name,
            'basel_category' => $incident->basel_category,
            'occurred_at' => $incident->occurred_at?->format('Y-m-d\TH:i'),
            'detected_at' => $incident->detected_at->format('Y-m-d\TH:i'),
            'reporter_external_source' => $incident->reporter_external_source,
            'assigned_to' => $incident->assigned_to,
            'financial_impact' => $incident->financial_impact,
            'currency' => $incident->currency,
            'is_data_breach' => $incident->is_data_breach,
            'is_cyber_incident' => $incident->is_cyber_incident,
            'affects_customers' => $incident->affects_customers,
            'affected_customer_count' => $incident->affected_customer_count,
            'root_cause' => $incident->root_cause,
            'lessons_learned' => $incident->lessons_learned,
            'linked_risk_id' => $incident->linked_risk_id,
            'linked_control_id' => $incident->linked_control_id,
            'linked_policy_id' => $incident->linked_policy_id,
            'notifications_sent_count' => $incident->notifications()
                ->whereNotNull('notified_at')->count(),
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function categoryOptions(): array
    {
        return [
            ['value' => 'cyber', 'label' => 'Cyber'],
            ['value' => 'data_breach', 'label' => 'Data Breach'],
            ['value' => 'conduct', 'label' => 'Conduct'],
            ['value' => 'financial_crime', 'label' => 'Financial Crime'],
            ['value' => 'operational', 'label' => 'Operational'],
            ['value' => 'customer_protection', 'label' => 'Customer Protection'],
            ['value' => 'other', 'label' => 'Other'],
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function severityOptions(): array
    {
        return [
            ['value' => 'critical', 'label' => 'Critical'],
            ['value' => 'high', 'label' => 'High'],
            ['value' => 'medium', 'label' => 'Medium'],
            ['value' => 'low', 'label' => 'Low'],
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function statusOptions(): array
    {
        return [
            ['value' => 'detected', 'label' => 'Detected'],
            ['value' => 'triaged', 'label' => 'Triaged'],
            ['value' => 'investigating', 'label' => 'Investigating'],
            ['value' => 'remediation', 'label' => 'Remediation'],
            ['value' => 'resolved', 'label' => 'Resolved'],
            ['value' => 'closed', 'label' => 'Closed'],
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function baselOptions(): array
    {
        return [
            ['value' => 'internal_fraud', 'label' => 'Internal Fraud'],
            ['value' => 'external_fraud', 'label' => 'External Fraud'],
            ['value' => 'employment_practices', 'label' => 'Employment Practices & Workplace Safety'],
            ['value' => 'clients_products_business', 'label' => 'Clients, Products & Business Practices'],
            ['value' => 'damage_physical_assets', 'label' => 'Damage to Physical Assets'],
            ['value' => 'business_disruption', 'label' => 'Business Disruption & System Failures'],
            ['value' => 'execution_delivery_process', 'label' => 'Execution, Delivery & Process Management'],
            ['value' => 'other', 'label' => 'Other'],
        ];
    }

    /** @return array<int, array{value: int, label: string}> */
    private function risksOptions(): array
    {
        return Risk::withoutGlobalScopes()
            ->select(['id', 'reference', 'title'])
            ->orderBy('reference')
            ->get()
            ->map(fn ($r) => ['value' => $r->id, 'label' => "{$r->reference} – {$r->title}"])
            ->toArray();
    }

    /** @return array<int, array{value: int, label: string}> */
    private function controlsOptions(): array
    {
        return Control::withoutGlobalScopes()
            ->select(['id', 'reference', 'title'])
            ->orderBy('reference')
            ->get()
            ->map(fn ($c) => ['value' => $c->id, 'label' => "{$c->reference} – {$c->title}"])
            ->toArray();
    }

    /** @return array<int, array{value: int, label: string}> */
    private function policiesOptions(): array
    {
        return Policy::withoutGlobalScopes()
            ->select(['id', 'reference', 'title'])
            ->orderBy('reference')
            ->get()
            ->map(fn ($p) => ['value' => $p->id, 'label' => "{$p->reference} – {$p->title}"])
            ->toArray();
    }

    /** @return array<int, array{value: int, label: string}> */
    private function usersOptions(): array
    {
        return User::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => ['value' => $u->id, 'label' => $u->name])
            ->toArray();
    }
}
