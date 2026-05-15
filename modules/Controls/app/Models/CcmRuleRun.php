<?php

declare(strict_types=1);

namespace Modules\Controls\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CcmRuleRun extends Model
{
    public $timestamps = false;

    protected $table = 'ccm_rule_runs';

    protected $fillable = [
        'rule_name',
        'run_at',
        'status',
        'metric_value',
        'threshold',
        'details',
        'issue_id',
    ];

    protected $casts = [
        'run_at' => 'datetime',
        'metric_value' => 'decimal:4',
        'threshold' => 'decimal:4',
        'details' => 'array',
        'issue_id' => 'integer',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }
}
