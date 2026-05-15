<?php

declare(strict_types=1);

namespace Modules\Controls\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlTest extends Model
{
    use BelongsToTenant;
    use EmitsAuditEvent;

    protected $table = 'control_tests';

    protected $fillable = [
        'tenant_id',
        'control_id',
        'tested_by',
        'tested_at',
        'period_start',
        'period_end',
        'sample_size',
        'population_size',
        'confidence_level',
        'outcome',
        'evidence_url',
        'findings',
    ];

    protected $casts = [
        'tested_at' => 'datetime',
        'period_start' => 'date',
        'period_end' => 'date',
        'sample_size' => 'integer',
        'population_size' => 'integer',
        'confidence_level' => 'decimal:3',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'control_test';
    }

    public function control(): BelongsTo
    {
        return $this->belongsTo(Control::class);
    }

    public function tester(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'tested_by');
    }
}
