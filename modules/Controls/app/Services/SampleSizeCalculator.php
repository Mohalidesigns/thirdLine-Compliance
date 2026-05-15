<?php

declare(strict_types=1);

namespace Modules\Controls\Services;

class SampleSizeCalculator
{
    /**
     * Calculate the recommended sample size using AICPA / ISA 530 approximations.
     *
     * - Population < 50: test 100% (returns population size)
     * - Population 50-250: small-pop lookup table (approx 60 for 95% confidence)
     * - Population > 250: Cochran's formula with finite population correction
     */
    public function calculate(int $populationSize, float $confidenceLevel = 0.95, float $tolerableErrorRate = 0.05): int
    {
        if ($populationSize <= 0) {
            return 0;
        }

        if ($populationSize < 50) {
            return $populationSize;
        }

        if ($populationSize <= 250) {
            return $this->smallPopulationSample($populationSize, $confidenceLevel);
        }

        return $this->cochranFormula($populationSize, $confidenceLevel, $tolerableErrorRate);
    }

    private function smallPopulationSample(int $n, float $confidenceLevel): int
    {
        return match (true) {
            $confidenceLevel >= 0.99 => min($n, 90),
            $confidenceLevel >= 0.95 => min($n, 60),
            $confidenceLevel >= 0.90 => min($n, 45),
            default => min($n, 30),
        };
    }

    private function cochranFormula(int $n, float $confidenceLevel, float $tolerableErrorRate): int
    {
        $z = $this->zScore($confidenceLevel);
        $p = 0.5;
        $e = $tolerableErrorRate;

        $n0 = ($z * $z * $p * (1 - $p)) / ($e * $e);

        $nAdjusted = $n0 / (1 + ($n0 - 1) / $n);

        return (int) ceil($nAdjusted);
    }

    private function zScore(float $confidenceLevel): float
    {
        return match (true) {
            $confidenceLevel >= 0.99 => 2.576,
            $confidenceLevel >= 0.98 => 2.326,
            $confidenceLevel >= 0.95 => 1.960,
            $confidenceLevel >= 0.90 => 1.645,
            default => 1.645,
        };
    }
}
