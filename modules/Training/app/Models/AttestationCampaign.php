<?php

declare(strict_types=1);

namespace Modules\Training\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttestationCampaign extends Model
{
    use BelongsToTenant;
    use EmitsAuditEvent;

    protected $fillable = [
        'tenant_id',
        'code',
        'title',
        'body',
        'starts_at',
        'ends_at',
        'mandatory_for_roles',
        'status',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'mandatory_for_roles' => 'array',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'attestation_campaign';
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttestationRecord::class, 'campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check whether the given user has already signed this campaign.
     */
    public function hasSigned(User $user): bool
    {
        return $this->records()
            ->where('user_id', $user->id)
            ->exists();
    }
}
