<?php

declare(strict_types=1);

namespace Modules\Training\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Training extends Model
{
    use BelongsToTenant;
    use EmitsAuditEvent;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'code',
        'title',
        'description',
        'category',
        'is_mandatory',
        'target_roles',
        'sla_days',
        'source',
        'source_url',
        'created_by',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'target_roles' => 'array',
        'sla_days' => 'integer',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'training';
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
