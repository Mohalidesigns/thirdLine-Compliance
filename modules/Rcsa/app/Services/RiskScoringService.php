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

    /**
     * Build a threshold lookup map for a cycle — single DB query.
     *
     * Returns an array keyed by "{lob}:{category}" with values:
     *   ['acceptable_rating' => 'medium', 'breach_action' => '...']
     *
     * Call once per request; pass the result into breachesAppetiteForLoadedRisk()
     * for each row so no per-row DB hits occur.
     *
     * @return array<string, array{acceptable_rating: string, breach_action: string|null}>
     */
    public function thresholdMapForCycle(int $cycleId): array
    {
        $cycle = RiskAssessmentCycle::withoutGlobalScopes()->find($cycleId);

        if ($cycle === null) {
            return [];
        }

        $thresholds = RiskAppetiteThreshold::withoutGlobalScopes()
            ->where('lob', $cycle->lob)
            ->get(['lob', 'category', 'acceptable_rating', 'breach_action']);

        $map = [];
        foreach ($thresholds as $t) {
            $map["{$t->lob}:{$t->category}"] = [
                'acceptable_rating' => $t->acceptable_rating,
                'breach_action' => $t->breach_action,
            ];
        }

        return $map;
    }

    /**
     * Pure logic check — zero DB queries.
     *
     * The $risk must have residual_rating and category already loaded.
     * The $thresholdMap must be built via thresholdMapForCycle() first,
     * passing the cycle's lob so the keys are "{lob}:{category}".
     *
     * @param  array<string, array{acceptable_rating: string, breach_action: string|null}>  $thresholdMap
     */
    public function breachesAppetiteForLoadedRisk(Risk $risk, array $thresholdMap): bool
    {
        if ($risk->residual_rating === null) {
            return false;
        }

        // The map is pre-keyed by lob:category. We need the cycle's lob.
        // The map is built for a specific cycle's lob, so iterate to find matching category.
        // Keys are "{lob}:{category}" — extract any key for this category.
        $acceptable = null;
        foreach ($thresholdMap as $key => $entry) {
            [, $cat] = explode(':', $key, 2);
            if ($cat === $risk->category) {
                $acceptable = $entry['acceptable_rating'];
                break;
            }
        }

        if ($acceptable === null) {
            return false;
        }

        return (self::RATING_ORDER[$risk->residual_rating] ?? 0) > (self::RATING_ORDER[$acceptable] ?? 0);
    }

    /**
     * Single-risk appetite check. Hits the DB to load the risk and cycle.
     * Prefer breachesAppetiteForLoadedRisk() when iterating many risks.
     */
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
