<?php

declare(strict_types=1);

namespace Modules\Training\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingEnrollment extends Model
{
    use BelongsToTenant;
    use EmitsAuditEvent;

    protected $fillable = [
        'tenant_id',
        'training_id',
        'user_id',
        'enrolled_at',
        'due_at',
        'started_at',
        'completed_at',
        'score',
        'exempted_at',
        'exemption_reason',
        'status',
        'enrolled_by',
        'completed_by',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'due_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'exempted_at' => 'datetime',
        'score' => 'integer',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'training_enrollment';
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enroller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Days until due date. Negative when overdue.
     */
    public function daysUntilDue(): ?int
    {
        if ($this->due_at === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->due_at->startOfDay(), false);
    }
}
