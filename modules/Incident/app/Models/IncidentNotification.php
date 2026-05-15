<?php

declare(strict_types=1);

namespace Modules\Incident\Models;

use App\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentNotification extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'incident_id',
        'regulator',
        'deadline_at',
        'notified_at',
        'notification_reference',
        'status',
        'notes',
    ];

    protected $casts = [
        'deadline_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function regulatorLabel(): string
    {
        return match ($this->regulator) {
            'cbn_cyber' => 'CBN – Cyber (4h)',
            'cbn_general' => 'CBN – General (24h)',
            'ndpc' => 'NDPC (72h)',
            'nfiu' => 'NFIU',
            'sec' => 'SEC',
            'others' => 'Others',
            default => strtoupper($this->regulator),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'submitted' => 'info',
            'acknowledged' => 'success',
            'overdue' => 'danger',
            default => 'gray',
        };
    }

    public function hoursRemaining(): float
    {
        return round(now()->diffInMinutes($this->deadline_at, false) / 60, 1);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue' || ($this->notified_at === null && $this->deadline_at->isPast());
    }
}
