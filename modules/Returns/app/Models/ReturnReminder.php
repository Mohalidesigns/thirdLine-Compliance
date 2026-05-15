<?php

declare(strict_types=1);

namespace Modules\Returns\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnReminder extends Model
{
    use BelongsToTenant;
    use EmitsAuditEvent;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'return_run_id',
        'reminder_at',
        'days_before_due',
        'fired_at',
        'channel',
        'recipient_user_id',
        'created_at',
    ];

    protected $casts = [
        'reminder_at' => 'datetime',
        'fired_at' => 'datetime',
        'created_at' => 'datetime',
        'days_before_due' => 'integer',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'return_reminder';
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(ReturnRun::class, 'return_run_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
