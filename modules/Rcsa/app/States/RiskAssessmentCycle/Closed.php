<?php

declare(strict_types=1);

namespace Modules\Rcsa\States\RiskAssessmentCycle;

class Closed extends CycleState
{
    public static string $name = 'closed';

    public function label(): string
    {
        return 'Closed';
    }

    public function color(): string
    {
        return 'gray';
    }
}
