<?php

declare(strict_types=1);

namespace Modules\Policy\States\Policy;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class PolicyState extends State
{
    abstract public function label(): string;

    abstract public function color(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, InReview::class)
            ->allowTransition(InReview::class, Approved::class)
            ->allowTransition(InReview::class, Draft::class)
            ->allowTransition(Approved::class, Published::class)
            ->allowTransition(Published::class, InForce::class)
            ->allowTransition(InForce::class, UnderReview::class)
            ->allowTransition(UnderReview::class, InForce::class)
            ->allowTransition(UnderReview::class, Superseded::class);
    }
}
