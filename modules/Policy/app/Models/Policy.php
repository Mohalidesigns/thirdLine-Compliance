<?php

declare(strict_types=1);

namespace Modules\Policy\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Policy\States\Policy\Approved;
use Modules\Policy\States\Policy\Draft;
use Modules\Policy\States\Policy\InForce;
use Modules\Policy\States\Policy\InReview;
use Modules\Policy\States\Policy\PolicyState;
use Modules\Policy\States\Policy\Published;
use Modules\Policy\States\Policy\Superseded;
use Modules\Policy\States\Policy\UnderReview;
use Spatie\ModelStates\HasStates;

class Policy extends Model
{
    use BelongsToTenant;
    use EmitsAuditEvent;
    use HasStates;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'reference',
        'title',
        'category',
        'owner_team',
        'version',
        'state',
        'effective_date',
        'next_review_date',
        'summary',
        'body',
        'published_pdf_path',
    ];

    protected $attributes = [
        'version' => 1,
    ];

    protected $casts = [
        'version' => 'integer',
        'effective_date' => 'date',
        'next_review_date' => 'date',
        'state' => PolicyState::class,
    ];

    protected static function auditActionPrefix(): string
    {
        return 'policy';
    }

    protected static function booted(): void
    {
        static::creating(function (self $policy): void {
            if (empty($policy->reference)) {
                $max = static::withoutGlobalScopes()->max('id') ?? 0;
                $policy->reference = 'POL-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PolicyVersion::class);
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(PolicyAcknowledgement::class);
    }

    public function allowedTransitions(): array
    {
        $stateClass = get_class($this->state);

        $map = [
            Draft::class => [
                ['to' => 'in_review', 'label' => 'Submit for review', 'requires_note' => false],
            ],
            InReview::class => [
                ['to' => 'approved', 'label' => 'Approve', 'requires_note' => false],
                ['to' => 'draft', 'label' => 'Request changes', 'requires_note' => true],
            ],
            Approved::class => [
                ['to' => 'published', 'label' => 'Publish', 'requires_note' => false],
            ],
            Published::class => [
                ['to' => 'in_force', 'label' => 'Mark as In Force', 'requires_note' => false],
            ],
            InForce::class => [
                ['to' => 'under_review', 'label' => 'Start review', 'requires_note' => false],
            ],
            UnderReview::class => [
                ['to' => 'in_force', 'label' => 'Reaffirm', 'requires_note' => false],
                ['to' => 'superseded', 'label' => 'Supersede', 'requires_note' => true],
            ],
            Superseded::class => [],
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

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'aml' => 'AML & CFT',
            'data_protection' => 'Data Protection',
            'risk' => 'Risk Management',
            'conduct' => 'Conduct',
            'cyber' => 'Cybersecurity',
            'governance' => 'Governance',
            'other' => 'Other',
            default => ucfirst($this->category),
        };
    }
}
