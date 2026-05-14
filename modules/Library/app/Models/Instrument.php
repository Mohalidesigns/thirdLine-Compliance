<?php

declare(strict_types=1);

namespace Modules\Library\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instrument extends Model
{
    use BelongsToTenant;
    use EmitsAuditEvent;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'source_title',
        'objectives',
        'date_issue',
        'date_commence',
        'date_repeal',
        'regulator_id',
        'instrument_type_id',
        'nature_id',
        'status_id',
        'area_of_focus_id',
        'risk_rating_id',
        'risk_rating_explain',
        'commercial_bank_relevance',
        'commercial_bank_compliance_context',
        'applicability',
        'link_url',
        'parent_id',
    ];

    protected $casts = [
        'date_issue' => 'date',
        'date_commence' => 'date',
        'date_repeal' => 'date',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'instrument';
    }

    public function regulator(): BelongsTo
    {
        return $this->belongsTo(Regulator::class);
    }

    public function instrumentType(): BelongsTo
    {
        return $this->belongsTo(InstrumentType::class);
    }

    public function nature(): BelongsTo
    {
        return $this->belongsTo(Nature::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function areaOfFocus(): BelongsTo
    {
        return $this->belongsTo(AreaOfFocus::class);
    }

    public function riskRating(): BelongsTo
    {
        return $this->belongsTo(RiskRating::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Instrument::class, 'parent_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(Instrument::class, 'parent_id');
    }

    public function obligations(): HasMany
    {
        return $this->hasMany(Obligation::class);
    }
}
