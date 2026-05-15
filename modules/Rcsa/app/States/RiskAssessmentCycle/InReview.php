<?php

declare(strict_types=1);

namespace Modules\Rcsa\States\RiskAssessmentCycle;

class InReview extends CycleState
{
    public static string $name = 'in_review';

    public function label(): string
    {
        return 'In Review';
    }

    public function color(): string
    {
        return 'purple';
    }
}
