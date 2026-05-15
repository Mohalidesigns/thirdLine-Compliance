<?php

declare(strict_types=1);

namespace Modules\Incident\Models;

use App\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalLossEvent extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'incident_id',
        'basel_category',
        'gross_loss',
        'recovery_amount',
        'net_loss_currency',
        'event_date',
        'recognized_date',
    ];

    protected $casts = [
        'gross_loss' => 'decimal:2',
        'recovery_amount' => 'decimal:2',
        'event_date' => 'date',
        'recognized_date' => 'date',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function netLoss(): string
    {
        return number_format(
            (float) $this->gross_loss - (float) $this->recovery_amount,
            2,
        );
    }
}
