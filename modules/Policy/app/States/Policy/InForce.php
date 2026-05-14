<?php

declare(strict_types=1);

namespace Modules\Policy\States\Policy;

class InForce extends PolicyState
{
    public static string $name = 'in_force';

    public function label(): string
    {
        return 'In Force';
    }

    public function color(): string
    {
        return 'green';
    }
}
