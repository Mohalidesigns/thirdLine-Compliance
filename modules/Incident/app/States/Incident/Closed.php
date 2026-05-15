<?php

declare(strict_types=1);

namespace Modules\Incident\States\Incident;

class Closed extends IncidentState
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
