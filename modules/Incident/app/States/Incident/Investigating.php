<?php

declare(strict_types=1);

namespace Modules\Incident\States\Incident;

class Investigating extends IncidentState
{
    public static string $name = 'investigating';

    public function label(): string
    {
        return 'Investigating';
    }

    public function color(): string
    {
        return 'warning';
    }
}
