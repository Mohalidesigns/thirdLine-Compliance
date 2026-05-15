<?php

declare(strict_types=1);

namespace Modules\Rcsa\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Risk extends Model
{
    use BelongsToTenant;
    use EmitsAuditEvent;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'cycle_id',
        'reference',
        'title',
        'description',
        'category',
        'risk_owner',
        'inherent_likelihood',
        'inherent_impact',
        'inherent_score',
        'inherent_rating',
        'residual_likelihood',
        'residual_impact',
        'residual_score',
        'residual_rating',
        'linked_obligation_ids',
        'mitigation_summary',
        'accept_basis',
    ];

    protected $casts = [
        'inherent_likelihood' => 'integer',
        'inherent_impact' => 'integer',
        'inherent_score' => 'integer',
        'residual_likelihood' => 'integer',
        'residual_impact' => 'integer',
        'residual_score' => 'integer',
        'linked_obligation_ids' => 'array',
    ];

    protected static function auditActionPrefix(): string
    {
        return 'risk';
    }

    protected static function booted(): void
    {
        static::creating(function (self $risk): void {
            if (empty($risk->getAttribute('reference'))) {
                $max = (int) (static::withoutGlobalScopes()->max('id') ?? 0);
                $risk->reference = 'RISK-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
            }

            if ($risk->getAttribute('linked_obligation_ids') === null) {
                $risk->linked_obligation_ids = [];
            }
        });

        static::saving(function (self $risk): void {
            $service = app(\Modules\Rcsa\Services\RiskScoringService::class);

            $cycle = $risk->relationLoaded('cycle')
                ? $risk->cycle
                : RiskAssessmentCycle::withoutGlobalScopes()->find($risk->cycle_id);
            $methodology = $cycle?->methodology ?? '3x3';

            if ($risk->inherent_likelihood !== null && $risk->inherent_impact !== null) {
                $inherent = $service->scoreFor($risk->inherent_likelihood, $risk->inherent_impact, $methodology);
                $risk->inherent_score = $inherent['score'];
                $risk->inherent_rating = $inherent['rating'];
            }

            if ($risk->residual_likelihood !== null && $risk->residual_impact !== null) {
                $residual = $service->scoreFor($risk->residual_likelihood, $risk->residual_impact, $methodology);
                $risk->residual_score = $residual['score'];
                $risk->residual_rating = $residual['rating'];
            }
        });
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(RiskAssessmentCycle::class, 'cycle_id');
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'operational' => 'Operational',
            'credit' => 'Credit',
            'market' => 'Market',
            'liquidity' => 'Liquidity',
            'compliance' => 'Compliance',
            'reputational' => 'Reputational',
            'strategic' => 'Strategic',
            'cyber' => 'Cyber',
            'aml' => 'AML & CFT',
            'conduct' => 'Conduct',
            'other' => 'Other',
            default => ucfirst($this->category),
        };
    }
}
