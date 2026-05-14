<?php

declare(strict_types=1);

namespace Modules\Policy\States\Policy;

class Published extends PolicyState
{
    public static string $name = 'published';

    public function label(): string
    {
        return 'Published';
    }

    public function color(): string
    {
        return 'cyan';
    }
}
