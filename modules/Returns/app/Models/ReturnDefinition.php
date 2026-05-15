<?php

declare(strict_types=1);

namespace Modules\Returns\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnDefinition extends Model
{
    use BelongsToTenant;
    use EmitsAuditEvent;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'code',
        'title',
        'description',
        'acts',
        'legal_basis',
        'regulator',
        'submission_channel',
        'file_format',
        'frequency',
        'responsible_unit',
        'approval_matrix',
        'evidence_required',
        'active',
    ];

    protected $casts = [
        'approval_matrix' => 'array',
        'evidence_required' => 'boolean',
        'active' => 'boolean',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'return_definition';
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ReturnRun::class);
    }

    /**
     * Human-readable regulator label.
     */
    public function regulatorLabel(): string
    {
        return self::regulatorLabels()[$this->regulator] ?? strtoupper($this->regulator);
    }

    /**
     * Human-readable frequency label.
     */
    public function frequencyLabel(): string
    {
        return self::frequencyLabels()[$this->frequency] ?? ucfirst($this->frequency);
    }

    /** @return array<string, string> */
    public static function regulatorLabels(): array
    {
        return [
            'cbn' => 'CBN',
            'ndic' => 'NDIC',
            'nfiu' => 'NFIU',
            'sec' => 'SEC',
            'ndpc' => 'NDPC',
            'firs' => 'FIRS',
            'pencom' => 'PenCom',
            'scuml' => 'SCUML',
            'other' => 'Other',
        ];
    }

    /** @return array<string, string> */
    public static function frequencyLabels(): array
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'half_year' => 'Half-Yearly',
            'annual' => 'Annual',
            'event_driven' => 'Event-Driven',
            'ad_hoc' => 'Ad-Hoc',
        ];
    }
}
