<?php

declare(strict_types=1);

namespace Modules\Incident\Models;

use App\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentAction extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'incident_id',
        'type',
        'title',
        'description',
        'owner_user_id',
        'due_at',
        'completed_at',
        'completed_by',
        'evidence_notes',
    ];

    protected $casts = [
        'due_at' => 'date',
        'completed_at' => 'datetime',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isOverdue(): bool
    {
        return $this->completed_at === null && $this->due_at !== null && $this->due_at->isPast();
    }
}
