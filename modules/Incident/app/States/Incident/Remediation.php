<?php

declare(strict_types=1);

namespace Modules\Incident\States\Incident;

class Remediation extends IncidentState
{
    public static string $name = 'remediation';

    public function label(): string
    {
        return 'Remediation';
    }

    public function color(): string
    {
        return 'orange';
    }
}
