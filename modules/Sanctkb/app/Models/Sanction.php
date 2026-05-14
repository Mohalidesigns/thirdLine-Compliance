<?php

declare(strict_types=1);

namespace Modules\Sanctkb\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\Library\Models\Regulator;

class Sanction extends Model
{
    use BelongsToTenant;
    use EmitsAuditEvent;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'regulator_id',
        'reference',
        'section',
        'offence',
        'party_name',
        'party_type',
        'amount_naira',
        'penalty_type',
        'effective_date',
        'source_url',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'amount_naira' => 'decimal:2',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'sanction';
    }

    public function regulator(): BelongsTo
    {
        return $this->belongsTo(Regulator::class);
    }

    public function scopeFullText(Builder $query, string $search): Builder
    {
        if (DB::getDriverName() === 'pgsql') {
            return $query->whereRaw(
                "content @@ plainto_tsquery('english', ?)",
                [$search]
            );
        }

        // SQLite fallback for tests
        return $query->where(function (Builder $q) use ($search): void {
            $q->where('offence', 'like', "%{$search}%")
                ->orWhere('party_name', 'like', "%{$search}%")
                ->orWhere('reference', 'like', "%{$search}%");
        });
    }
}
