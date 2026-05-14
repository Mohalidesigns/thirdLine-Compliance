<?php

declare(strict_types=1);

namespace Modules\Policy\States\Policy;

class Approved extends PolicyState
{
    public static string $name = 'approved';

    public function label(): string
    {
        return 'Approved';
    }

    public function color(): string
    {
        return 'purple';
    }
}
