<?php

declare(strict_types=1);

namespace Modules\Policy\States\Policy;

class Draft extends PolicyState
{
    public static string $name = 'draft';

    public function label(): string
    {
        return 'Draft';
    }

    public function color(): string
    {
        return 'gray';
    }
}
