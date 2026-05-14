<?php

declare(strict_types=1);

namespace Modules\Library\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Obligation extends Model
{
    use BelongsToTenant;
    use EmitsAuditEvent;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'instrument_id',
        'reference',
        'title',
        'description',
        'due_basis',
        'frequency',
        'next_due_date',
        'responsible_team',
        'status',
    ];

    protected $casts = [
        'next_due_date' => 'date',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'obligation';
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }
}
