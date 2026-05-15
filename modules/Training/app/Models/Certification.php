<?php

declare(strict_types=1);

namespace Modules\Training\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certification extends Model
{
    use BelongsToTenant;
    use EmitsAuditEvent;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'issuing_body',
        'certificate_no',
        'issued_at',
        'expires_at',
        'evidence_path',
        'status',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'certification';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Returns the number of days until expiry.
     * Returns null if there is no expiry date.
     * Returns a negative number if already expired.
     */
    public function daysUntilExpiry(): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expires_at->startOfDay(), false);
    }
}
