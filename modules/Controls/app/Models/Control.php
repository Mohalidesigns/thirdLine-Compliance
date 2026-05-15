<?php

declare(strict_types=1);

namespace Modules\Controls\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Concerns\Auditable;

class Control extends Model
{
    use Auditable;
    use BelongsToTenant;
    use EmitsAuditEvent;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'reference',
        'title',
        'description',
        'control_type',
        'nature',
        'frequency',
        'owner_team',
        'linked_obligation_ids',
        'linked_risk_ids',
        'status',
        'last_tested_at',
        'next_test_due',
    ];

    protected $casts = [
        'linked_obligation_ids' => 'array',
        'linked_risk_ids' => 'array',
        'last_tested_at' => 'datetime',
        'next_test_due' => 'date',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'control';
    }

    protected static function booted(): void
    {
        static::creating(function (self $control): void {
            if (empty($control->getAttribute('reference'))) {
                $max = (int) (static::withoutGlobalScopes()->max('id') ?? 0);
                $control->reference = 'CTL-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
            }

            if ($control->getAttribute('linked_obligation_ids') === null) {
                $control->linked_obligation_ids = [];
            }

            if ($control->getAttribute('linked_risk_ids') === null) {
                $control->linked_risk_ids = [];
            }
        });
    }

    public function tests(): HasMany
    {
        return $this->hasMany(ControlTest::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class, 'linked_control_id');
    }

    public function frequencyLabel(): string
    {
        return match ($this->frequency) {
            'continuous' => 'Continuous',
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'semiannual' => 'Semi-Annual',
            'annual' => 'Annual',
            'event_driven' => 'Event-Driven',
            default => ucfirst($this->frequency),
        };
    }

    public function daysUntilDue(): ?int
    {
        if ($this->next_test_due === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->next_test_due->startOfDay(), false);
    }

    public function frequencyInterval(): \DateInterval
    {
        return match ($this->frequency) {
            'continuous' => new \DateInterval('P1D'),
            'daily' => new \DateInterval('P1D'),
            'weekly' => new \DateInterval('P7D'),
            'monthly' => new \DateInterval('P1M'),
            'quarterly' => new \DateInterval('P3M'),
            'semiannual' => new \DateInterval('P6M'),
            'annual' => new \DateInterval('P1Y'),
            'event_driven' => new \DateInterval('P1M'),
            default => new \DateInterval('P1M'),
        };
    }
}
