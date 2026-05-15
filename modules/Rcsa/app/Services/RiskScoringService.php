<?php

declare(strict_types=1);

namespace Modules\Rcsa\Services;

use Modules\Rcsa\Models\Risk;
use Modules\Rcsa\Models\RiskAppetiteThreshold;
use Modules\Rcsa\Models\RiskAssessmentCycle;

class RiskScoringService
{
    private const RATING_ORDER = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];

    /**
     * @return array{score: int, rating: string}
     */
    public function scoreFor(int $likelihood, int $impact, string $methodology): array
    {
        $score = $likelihood * $impact;

        $rating = match ($methodology) {
            '5x5' => $this->rateFor5x5($score),
            default => $this->rateFor3x3($score),
        };

        return ['score' => $score, 'rating' => $rating];
    }

    public function appetiteFor(int $cycleId, string $category): ?string
    {
        $cycle = RiskAssessmentCycle::withoutGlobalScopes()->find($cycleId);

        if ($cycle === null) {
            return null;
        }

        $threshold = RiskAppetiteThreshold::withoutGlobalScopes()
            ->where('lob', $cycle->lob)
            ->where('category', $category)
            ->first();

        return $threshold?->acceptable_rating;
    }

    public function breachesAppetite(int $riskId): bool
    {
        $risk = Risk::withoutGlobalScopes()->find($riskId);

        if ($risk === null || $risk->residual_rating === null) {
            return false;
        }

        $acceptable = $this->appetiteFor($risk->cycle_id, $risk->category);

        if ($acceptable === null) {
            return false;
        }

        return (self::RATING_ORDER[$risk->residual_rating] ?? 0) > (self::RATING_ORDER[$acceptable] ?? 0);
    }

    private function rateFor3x3(int $score): string
    {
        return match (true) {
            $score <= 2 => 'low',
            $score <= 4 => 'medium',
            $score <= 6 => 'high',
            default => 'critical',
        };
    }

    private function rateFor5x5(int $score): string
    {
        return match (true) {
            $score <= 6 => 'low',
            $score <= 12 => 'medium',
            $score <= 20 => 'high',
            default => 'critical',
        };
    }
}
