<?php

declare(strict_types=1);

namespace Modules\Policy\States\Policy;

class UnderReview extends PolicyState
{
    public static string $name = 'under_review';

    public function label(): string
    {
        return 'Under Review';
    }

    public function color(): string
    {
        return 'orange';
    }
}
