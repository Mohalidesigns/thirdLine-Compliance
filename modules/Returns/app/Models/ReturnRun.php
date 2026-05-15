<?php

declare(strict_types=1);

namespace Modules\Returns\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnRun extends Model
{
    use BelongsToTenant;
    use EmitsAuditEvent;

    protected $fillable = [
        'tenant_id',
        'return_definition_id',
        'period_label',
        'period_start',
        'period_end',
        'due_at',
        'status',
        'maker_id',
        'checker_id',
        'approver_id',
        'payload_path',
        'submission_reference',
        'submitted_at',
        'acknowledged_at',
        'acknowledgement_path',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'due_at' => 'datetime',
        'submitted_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'return_run';
    }

    // ---------------------------------------------------------------------------
    // Relations
    // ---------------------------------------------------------------------------

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ReturnDefinition::class, 'return_definition_id');
    }

    public function maker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'maker_id');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checker_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ReturnApproval::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(ReturnReminder::class);
    }

    // ---------------------------------------------------------------------------
    // Domain helpers
    // ---------------------------------------------------------------------------

    /**
     * True when the run is past its due_at and not yet submitted/acknowledged.
     */
    public function isOverdue(): bool
    {
        if (in_array($this->status, ['submitted_pending_ack', 'acknowledged', 'rejected'], true)) {
            return false;
        }

        return $this->due_at->isPast();
    }

    /**
     * Days until due_at. Negative when overdue.
     */
    public function daysUntilDue(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->due_at->startOfDay(), false);
    }

    /**
     * Human-readable status label.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'scheduled' => 'Scheduled',
            'in_progress' => 'In Progress',
            'submitted_pending_ack' => 'Pending Acknowledgement',
            'acknowledged' => 'Acknowledged',
            'late' => 'Late',
            'rejected' => 'Rejected',
            default => ucfirst((string) $this->status),
        };
    }

    /**
     * Filament/badge color for the status.
     */
    public function statusColor(): string
    {
        return match ($this->status) {
            'scheduled' => 'gray',
            'in_progress' => 'info',
            'submitted_pending_ack' => 'warning',
            'acknowledged' => 'success',
            'late' => 'danger',
            'rejected' => 'danger',
            default => 'gray',
        };
    }
}
