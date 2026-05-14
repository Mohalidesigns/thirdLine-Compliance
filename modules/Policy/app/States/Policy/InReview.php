<?php

declare(strict_types=1);

namespace Modules\Policy\States\Policy;

class InReview extends PolicyState
{
    public static string $name = 'in_review';

    public function label(): string
    {
        return 'In Review';
    }

    public function color(): string
    {
        return 'blue';
    }
}
