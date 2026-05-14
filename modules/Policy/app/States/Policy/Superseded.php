<?php

declare(strict_types=1);

namespace Modules\Policy\States\Policy;

class Superseded extends PolicyState
{
    public static string $name = 'superseded';

    public function label(): string
    {
        return 'Superseded';
    }

    public function color(): string
    {
        return 'gray';
    }
}
