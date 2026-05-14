<?php

declare(strict_types=1);

namespace Modules\Policy\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyAcknowledgement extends Model
{
    use BelongsToTenant;
    use EmitsAuditEvent;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'policy_id',
        'user_id',
        'acknowledged_at',
        'policy_version',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'policy_version' => 'integer',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'policy_acknowledgement';
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
