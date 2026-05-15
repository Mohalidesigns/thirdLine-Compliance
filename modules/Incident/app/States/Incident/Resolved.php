<?php

declare(strict_types=1);

namespace Modules\Incident\States\Incident;

class Resolved extends IncidentState
{
    public static string $name = 'resolved';

    public function label(): string
    {
        return 'Resolved';
    }

    public function color(): string
    {
        return 'success';
    }
}
