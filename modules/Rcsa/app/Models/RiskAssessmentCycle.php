<?php

declare(strict_types=1);

namespace Modules\Rcsa\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Rcsa\States\RiskAssessmentCycle\Closed;
use Modules\Rcsa\States\RiskAssessmentCycle\CycleState;
use Modules\Rcsa\States\RiskAssessmentCycle\DataCapture;
use Modules\Rcsa\States\RiskAssessmentCycle\InReview;
use Modules\Rcsa\States\RiskAssessmentCycle\Planning;
use Modules\Rcsa\States\RiskAssessmentCycle\Scoring;
use Modules\Rcsa\States\RiskAssessmentCycle\SignedOff;
use Spatie\ModelStates\HasStates;

class RiskAssessmentCycle extends Model
{
    use BelongsToTenant;
    use EmitsAuditEvent;
    use HasStates;
    use SoftDeletes;

    protected $table = 'risk_assessment_cycles';

    protected $fillable = [
        'tenant_id',
        'reference',
        'name',
        'lob',
        'state',
        'cycle_year',
        'cycle_quarter',
        'started_at',
        'closed_at',
        'sla_due_date',
        'methodology',
        'summary',
        'lead_assessor_id',
    ];

    protected $casts = [
        'state' => CycleState::class,
        'cycle_year' => 'integer',
        'cycle_quarter' => 'integer',
        'started_at' => 'date',
        'closed_at' => 'date',
        'sla_due_date' => 'date',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'risk_assessment_cycle';
    }

    protected static function booted(): void
    {
        static::creating(function (self $cycle): void {
            if (empty($cycle->reference)) {
                $year = $cycle->cycle_year ?? now()->year;
                $max = static::withoutGlobalScopes()->max('id') ?? 0;
                $cycle->reference = 'BRA-'.$year.'-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function leadAssessor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'lead_assessor_id');
    }

    public function risks(): HasMany
    {
        return $this->hasMany(Risk::class, 'cycle_id');
    }

    public function workshopNotes(): HasMany
    {
        return $this->hasMany(RiskWorkshopNote::class, 'cycle_id');
    }

    public function allowedTransitions(): array
    {
        $stateClass = get_class($this->state);

        $map = [
            Planning::class => [
                ['to' => 'data_capture', 'label' => 'Open Data Capture', 'requires_changes_note' => false],
            ],
            DataCapture::class => [
                ['to' => 'scoring', 'label' => 'Open Scoring', 'requires_changes_note' => false],
            ],
            Scoring::class => [
                ['to' => 'in_review', 'label' => 'Submit for Review', 'requires_changes_note' => false],
            ],
            InReview::class => [
                ['to' => 'signed_off', 'label' => 'Sign Off', 'requires_changes_note' => false],
                ['to' => 'scoring', 'label' => 'Request Changes', 'requires_changes_note' => true],
            ],
            SignedOff::class => [
                ['to' => 'closed', 'label' => 'Close', 'requires_changes_note' => false],
            ],
            Closed::class => [],
        ];

        return $map[$stateClass] ?? [];
    }

    public function stateLabel(): string
    {
        return $this->state->label();
    }

    public function stateColor(): string
    {
        return $this->state->color();
    }

    public function slaDaysRemaining(): ?int
    {
        if ($this->sla_due_date === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->sla_due_date->startOfDay(), false);
    }
}
