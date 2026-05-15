<?php

declare(strict_types=1);

namespace Modules\Incident\States\Incident;

class Detected extends IncidentState
{
    public static string $name = 'detected';

    public function label(): string
    {
        return 'Detected';
    }

    public function color(): string
    {
        return 'gray';
    }
}
