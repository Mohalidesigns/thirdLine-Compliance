<?php

declare(strict_types=1);

namespace Modules\Incident\States\Incident;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class IncidentState extends State
{
    abstract public function label(): string;

    abstract public function color(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Detected::class)
            ->allowTransition(Detected::class, Triaged::class)
            ->allowTransition(Triaged::class, Investigating::class)
            ->allowTransition(Investigating::class, Remediation::class)
            ->allowTransition(Remediation::class, Resolved::class)
            ->allowTransition(Resolved::class, Closed::class);
    }
}
