<?php

declare(strict_types=1);

namespace Modules\Rcsa\States\RiskAssessmentCycle;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class CycleState extends State
{
    abstract public function label(): string;

    abstract public function color(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Planning::class)
            ->allowTransition(Planning::class, DataCapture::class)
            ->allowTransition(DataCapture::class, Scoring::class)
            ->allowTransition(Scoring::class, InReview::class)
            ->allowTransition(InReview::class, SignedOff::class)
            ->allowTransition(InReview::class, Scoring::class)
            ->allowTransition(SignedOff::class, Closed::class);
    }
}
