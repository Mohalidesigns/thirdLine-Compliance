<?php

declare(strict_types=1);

namespace Modules\Incident\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Concerns\Auditable;
use Modules\Incident\States\Incident\Closed;
use Modules\Incident\States\Incident\Detected;
use Modules\Incident\States\Incident\IncidentState;
use Modules\Incident\States\Incident\Investigating;
use Modules\Incident\States\Incident\Remediation;
use Modules\Incident\States\Incident\Resolved;
use Modules\Incident\States\Incident\Triaged;
use Spatie\ModelStates\HasStates;

class Incident extends Model
{
    use Auditable;
    use BelongsToTenant;
    use EmitsAuditEvent;
    use HasStates;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'code',
        'title',
        'description',
        'category',
        'severity',
        'basel_category',
        'status',
        'occurred_at',
        'detected_at',
        'reported_by',
        'reporter_external_source',
        'assigned_to',
        'financial_impact',
        'currency',
        'is_data_breach',
        'is_cyber_incident',
        'affects_customers',
        'affected_customer_count',
        'root_cause',
        'lessons_learned',
        'linked_risk_id',
        'linked_control_id',
        'linked_policy_id',
        'closed_at',
        'closed_by',
        'closure_evidence_count',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'detected_at' => 'datetime',
        'closed_at' => 'datetime',
        'is_data_breach' => 'boolean',
        'is_cyber_incident' => 'boolean',
        'affects_customers' => 'boolean',
        'affected_customer_count' => 'integer',
        'financial_impact' => 'decimal:2',
        'closure_evidence_count' => 'integer',
        'status' => IncidentState::class,
    ];

    protected static function auditActionPrefix(): string
    {
        return 'incident';
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(IncidentAction::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(IncidentNotification::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(IncidentEvidence::class);
    }

    public function lossEvent(): HasOne
    {
        return $this->hasOne(OperationalLossEvent::class);
    }

    public function statusLabel(): string
    {
        return $this->status->label();
    }

    public function statusColor(): string
    {
        return $this->status->color();
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'cyber' => 'Cyber',
            'data_breach' => 'Data Breach',
            'conduct' => 'Conduct',
            'financial_crime' => 'Financial Crime',
            'operational' => 'Operational',
            'customer_protection' => 'Customer Protection',
            'other' => 'Other',
            default => ucfirst($this->category),
        };
    }

    public function severityLabel(): string
    {
        return match ($this->severity) {
            'critical' => 'Critical',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
            default => ucfirst($this->severity),
        };
    }

    public function baselCategoryLabel(): string
    {
        return match ($this->basel_category) {
            'internal_fraud' => 'Internal Fraud',
            'external_fraud' => 'External Fraud',
            'employment_practices' => 'Employment Practices & Workplace Safety',
            'clients_products_business' => 'Clients, Products & Business Practices',
            'damage_physical_assets' => 'Damage to Physical Assets',
            'business_disruption' => 'Business Disruption & System Failures',
            'execution_delivery_process' => 'Execution, Delivery & Process Management',
            'other' => 'Other',
            default => ucfirst((string) $this->basel_category),
        };
    }

    public function daysOpen(): int
    {
        $end = $this->closed_at ?? now();

        return (int) $this->detected_at->diffInDays($end);
    }

    /** @return array<int, array{value: string, label: string}> */
    public function allowedTransitions(): array
    {
        $stateClass = get_class($this->status);

        $map = [
            Detected::class => [
                ['value' => 'triaged', 'label' => 'Mark Triaged'],
            ],
            Triaged::class => [
                ['value' => 'investigating', 'label' => 'Start Investigation'],
            ],
            Investigating::class => [
                ['value' => 'remediation', 'label' => 'Move to Remediation'],
            ],
            Remediation::class => [
                ['value' => 'resolved', 'label' => 'Mark Resolved'],
            ],
            Resolved::class => [
                ['value' => 'closed', 'label' => 'Close Incident'],
            ],
            Closed::class => [],
        ];

        return $map[$stateClass] ?? [];
    }
}
