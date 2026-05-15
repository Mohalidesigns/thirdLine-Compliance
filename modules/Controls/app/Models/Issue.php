<?php

declare(strict_types=1);

namespace Modules\Controls\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Concerns\Auditable;

class Issue extends Model
{
    use Auditable;
    use BelongsToTenant;
    use EmitsAuditEvent;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'reference',
        'source_type',
        'source_id',
        'title',
        'description',
        'severity',
        'status',
        'due_date',
        'owner_team',
        'linked_control_id',
        'linked_risk_id',
        'linked_obligation_id',
        'linked_policy_id',
        'resolution_notes',
        'resolved_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'resolved_at' => 'datetime',
        'source_id' => 'integer',
        'linked_control_id' => 'integer',
        'linked_risk_id' => 'integer',
        'linked_obligation_id' => 'integer',
        'linked_policy_id' => 'integer',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'issue';
    }

    protected static function booted(): void
    {
        static::creating(function (self $issue): void {
            if (empty($issue->reference)) {
                $max = static::withoutGlobalScopes()->max('id') ?? 0;
                $issue->reference = 'ISS-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function control(): BelongsTo
    {
        return $this->belongsTo(Control::class, 'linked_control_id');
    }
}
