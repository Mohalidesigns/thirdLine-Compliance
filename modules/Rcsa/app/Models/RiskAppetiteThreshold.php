<?php

declare(strict_types=1);

namespace Modules\Rcsa\Models;

use App\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class RiskAppetiteThreshold extends Model
{
    use BelongsToTenant;

    protected $table = 'risk_appetite_thresholds';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'lob',
        'category',
        'acceptable_rating',
        'breach_action',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];
}
