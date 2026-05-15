<?php

declare(strict_types=1);

namespace Modules\Incident\Services;

use App\Services\AuditWriter;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Incident\Models\Incident;
use Modules\Incident\Models\IncidentEvidence;
use Modules\Incident\Models\IncidentNotification;
use Modules\Incident\Models\OperationalLossEvent;
use Modules\Incident\States\Incident\Closed;
use Modules\Incident\States\Incident\Detected;
use Modules\Incident\States\Incident\IncidentState;
use Modules\Incident\States\Incident\Investigating;
use Modules\Incident\States\Incident\Remediation;
use Modules\Incident\States\Incident\Resolved;
use Modules\Incident\States\Incident\Triaged;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

class IncidentService
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {}

    /**
     * Paginate incidents with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginatedList(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = Incident::query()
            ->with(['reportedBy:id,name', 'assignedTo:id,name'])
            ->withCount([
                'notifications as notifications_pending' => fn ($q) => $q->where('status', 'pending'),
                'notifications as notifications_overdue' => fn ($q) => $q->where('status', 'overdue'),
                'actions as open_actions' => fn ($q) => $q->whereNull('completed_at'),
            ])
            ->orderByDesc('detected_at');

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($term, $likeOp): void {
                $q->where('title', $likeOp, "%{$term}%")
                    ->orWhere('code', $likeOp, "%{$term}%");
            });
        }

        foreach (['category', 'severity', 'status', 'basel_category'] as $filter) {
            if (! empty($filters[$filter])) {
                $query->where($filter, $filters[$filter]);
            }
        }

        if (isset($filters['is_data_breach']) && $filters['is_data_breach'] !== null) {
            $query->where('is_data_breach', (bool) $filters['is_data_breach']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function find(int $id): Incident
    {
        return Incident::findOrFail($id);
    }

    /**
     * Create a new incident. Generates the next INC-YYYY-NNNN code and
     * schedules regulator notifications automatically when applicable.
     */
    public function create(array $data, int $reporterId): Incident
    {
        return DB::transaction(function () use ($data, $reporterId): Incident {
            $data['reported_by'] = $reporterId;
            $data['code'] = $this->nextCode();

            if (empty($data['detected_at'])) {
                $data['detected_at'] = now();
            }

            // Default currency when financial_impact is set
            if (! empty($data['financial_impact']) && empty($data['currency'])) {
                $data['currency'] = 'NGN';
            }

            $incident = Incident::create($data);

            if ($incident->is_cyber_incident || $incident->is_data_breach) {
                $this->scheduleRegulatorNotifications($incident);
            }

            return $incident;
        });
    }

    /**
     * Transition the incident to a new state. Guards the resolved→closed
     * transition by checking closure_evidence_count > 0.
     *
     * @throws \InvalidArgumentException when the target state is unknown
     * @throws \RuntimeException when close guard fails
     * @throws TransitionNotFound when the Spatie state machine rejects it
     */
    public function transition(Incident $incident, string $to, int $actorId): Incident
    {
        $stateMap = $this->stateMap();

        if (! isset($stateMap[$to])) {
            throw new \InvalidArgumentException("Unknown target state: {$to}");
        }

        if ($to === 'closed' && $incident->closure_evidence_count < 1) {
            throw new \RuntimeException('Cannot close incident: at least one evidence item is required.');
        }

        return DB::transaction(function () use ($incident, $to, $stateMap, $actorId): Incident {
            $previousState = $incident->status::$name;
            $toClass = $stateMap[$to];

            $incident->status->transitionTo($toClass);

            if ($to === 'closed') {
                $incident->closed_at = now();
                $incident->closed_by = $actorId;
            }

            $incident->save();

            $this->auditWriter->record(
                action: 'incident.state_transitioned',
                subject: $incident,
                context: [
                    'before' => ['status' => $previousState],
                    'after' => ['status' => $to],
                    'actor_id' => $actorId,
                ],
            );

            return $incident->fresh();
        });
    }

    /**
     * Schedule regulator notification rows based on SLA rules.
     * CBN Cyber: 4h internal + 24h external.
     * NDPC: 72h for data breaches.
     */
    public function scheduleRegulatorNotifications(Incident $incident): void
    {
        $detectedAt = $incident->detected_at;

        if ($incident->is_cyber_incident) {
            // 4h internal CBN cyber
            IncidentNotification::firstOrCreate(
                ['incident_id' => $incident->id, 'regulator' => 'cbn_cyber'],
                [
                    'tenant_id' => $incident->tenant_id,
                    'deadline_at' => $detectedAt->copy()->addHours(4),
                    'status' => 'pending',
                ],
            );

            // 24h external CBN general
            IncidentNotification::firstOrCreate(
                ['incident_id' => $incident->id, 'regulator' => 'cbn_general'],
                [
                    'tenant_id' => $incident->tenant_id,
                    'deadline_at' => $detectedAt->copy()->addHours(24),
                    'status' => 'pending',
                ],
            );
        }

        if ($incident->is_data_breach) {
            // 72h NDPC
            IncidentNotification::firstOrCreate(
                ['incident_id' => $incident->id, 'regulator' => 'ndpc'],
                [
                    'tenant_id' => $incident->tenant_id,
                    'deadline_at' => $detectedAt->copy()->addHours(72),
                    'status' => 'pending',
                ],
            );
        }
    }

    /**
     * Record a regulator notification submission.
     */
    public function recordNotification(
        IncidentNotification $notification,
        string $reference,
        int $actorId,
    ): IncidentNotification {
        return DB::transaction(function () use ($notification, $reference, $actorId): IncidentNotification {
            $notification->update([
                'status' => 'submitted',
                'notified_at' => now(),
                'notification_reference' => $reference,
            ]);

            $this->auditWriter->record(
                action: 'incident.notification_submitted',
                subject: $notification->incident,
                context: [
                    'notification_id' => $notification->id,
                    'regulator' => $notification->regulator,
                    'reference' => $reference,
                    'actor_id' => $actorId,
                ],
            );

            return $notification->fresh();
        });
    }

    /**
     * Attach evidence to an incident. The observer updates closure_evidence_count.
     *
     * @param  array<string, mixed>  $data
     */
    public function attachEvidence(Incident $incident, array $data, int $uploaderId): IncidentEvidence
    {
        $data['incident_id'] = $incident->id;
        $data['tenant_id'] = $incident->tenant_id;
        $data['uploaded_by'] = $uploaderId;
        $data['created_at'] = now();

        return IncidentEvidence::create($data);
    }

    /**
     * Close an incident. Delegates to transition() which enforces the evidence guard.
     *
     * @throws \RuntimeException when no evidence exists
     */
    public function closeIncident(Incident $incident, int $actorId): Incident
    {
        return $this->transition($incident, 'closed', $actorId);
    }

    /**
     * Record (or replace) an operational loss event for this incident.
     *
     * @param  array<string, mixed>  $loss
     */
    public function recordLoss(Incident $incident, array $loss): OperationalLossEvent
    {
        return DB::transaction(function () use ($incident, $loss): OperationalLossEvent {
            $loss['tenant_id'] = $incident->tenant_id;
            $loss['incident_id'] = $incident->id;

            if (empty($loss['net_loss_currency'])) {
                $loss['net_loss_currency'] = 'NGN';
            }

            return OperationalLossEvent::updateOrCreate(
                ['incident_id' => $incident->id],
                $loss,
            );
        });
    }

    /**
     * Dashboard stats for the current tenant.
     *
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        $openStatuses = ['detected', 'triaged', 'investigating', 'remediation', 'resolved'];

        $total_open = Incident::whereIn('status', $openStatuses)->count();
        $critical_open = Incident::whereIn('status', $openStatuses)->where('severity', 'critical')->count();
        $breaches_open = Incident::whereIn('status', $openStatuses)->where('is_data_breach', true)->count();

        $today = now();
        $notifications_due_today = IncidentNotification::where('status', 'pending')
            ->whereDate('deadline_at', $today->toDateString())
            ->count();

        $notifications_overdue = IncidentNotification::where('status', 'overdue')->count();

        $year_start = now()->startOfYear();
        $loss_ytd = OperationalLossEvent::where('event_date', '>=', $year_start->toDateString())
            ->selectRaw('SUM(gross_loss - recovery_amount) as total')
            ->value('total') ?? 0;

        return [
            'total_open' => $total_open,
            'critical_open' => $critical_open,
            'breaches_open' => $breaches_open,
            'notifications_due_today' => $notifications_due_today,
            'notifications_overdue' => $notifications_overdue,
            'loss_ytd_ngn' => number_format((float) $loss_ytd, 2),
        ];
    }

    /**
     * Generate the next INC-YYYY-NNNN code, sequential per year across the tenant.
     * Uses a DB lock to prevent race conditions.
     */
    private function nextCode(): string
    {
        $year = now()->year;
        $prefix = "INC-{$year}-";

        // Lock the table row count for this year under the current tenant
        $tenantId = Incident::currentTenantId();

        $max = Incident::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('code', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->count();

        $seq = str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$seq}";
    }

    /** @return array<string, class-string<IncidentState>> */
    private function stateMap(): array
    {
        return [
            'detected' => Detected::class,
            'triaged' => Triaged::class,
            'investigating' => Investigating::class,
            'remediation' => Remediation::class,
            'resolved' => Resolved::class,
            'closed' => Closed::class,
        ];
    }
}
