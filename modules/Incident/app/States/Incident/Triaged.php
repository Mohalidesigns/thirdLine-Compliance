<?php

declare(strict_types=1);

namespace Modules\Incident\States\Incident;

class Triaged extends IncidentState
{
    public static string $name = 'triaged';

    public function label(): string
    {
        return 'Triaged';
    }

    public function color(): string
    {
        return 'blue';
    }
}
