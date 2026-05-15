<?php

declare(strict_types=1);

namespace Modules\Returns\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnApproval extends Model
{
    use BelongsToTenant;
    use EmitsAuditEvent;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'return_run_id',
        'step',
        'actor_id',
        'decision',
        'notes',
        'acted_at',
        'created_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'return_approval';
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(ReturnRun::class, 'return_run_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Human-readable step label.
     */
    public function stepLabel(): string
    {
        return match ($this->step) {
            'maker_submit' => 'Maker Submit',
            'checker_review' => 'Checker Review',
            'approver_sign_off' => 'Approver Sign-Off',
            default => ucfirst((string) $this->step),
        };
    }
}
