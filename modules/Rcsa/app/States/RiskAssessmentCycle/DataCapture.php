<?php

declare(strict_types=1);

namespace Modules\Rcsa\States\RiskAssessmentCycle;

class DataCapture extends CycleState
{
    public static string $name = 'data_capture';

    public function label(): string
    {
        return 'Data Capture';
    }

    public function color(): string
    {
        return 'blue';
    }
}
