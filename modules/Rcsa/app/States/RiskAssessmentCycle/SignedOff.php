<?php

declare(strict_types=1);

namespace Modules\Rcsa\States\RiskAssessmentCycle;

class SignedOff extends CycleState
{
    public static string $name = 'signed_off';

    public function label(): string
    {
        return 'Signed Off';
    }

    public function color(): string
    {
        return 'green';
    }
}
