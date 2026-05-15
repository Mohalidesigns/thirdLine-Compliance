<?php

declare(strict_types=1);

namespace Modules\Rcsa\States\RiskAssessmentCycle;

class Scoring extends CycleState
{
    public static string $name = 'scoring';

    public function label(): string
    {
        return 'Scoring';
    }

    public function color(): string
    {
        return 'orange';
    }
}
