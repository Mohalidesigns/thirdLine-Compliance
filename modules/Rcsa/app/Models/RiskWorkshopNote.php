<?php

declare(strict_types=1);

namespace Modules\Rcsa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskWorkshopNote extends Model
{
    public $timestamps = false;

    protected $table = 'risk_workshop_notes';

    protected $fillable = [
        'cycle_id',
        'recorded_by',
        'recorded_at',
        'attendees',
        'notes',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'attendees' => 'array',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(RiskAssessmentCycle::class, 'cycle_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'recorded_by');
    }
}
