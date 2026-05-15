<?php

declare(strict_types=1);

namespace Modules\Training\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttestationRecord extends Model
{
    use BelongsToTenant;
    use EmitsAuditEvent;

    /**
     * The model has no updated_at column; only created_at.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'user_id',
        'signed_at',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'attestation_record';
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AttestationCampaign::class, 'campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
