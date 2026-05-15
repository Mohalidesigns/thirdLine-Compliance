<?php

declare(strict_types=1);

namespace Modules\Rcsa\States\RiskAssessmentCycle;

class Planning extends CycleState
{
    public static string $name = 'planning';

    public function label(): string
    {
        return 'Planning';
    }

    public function color(): string
    {
        return 'gray';
    }
}
