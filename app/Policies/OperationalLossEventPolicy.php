<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Incident\Models\OperationalLossEvent;

class OperationalLossEventPolicy
{
    public function view(User $user, OperationalLossEvent $event): bool
    {
        return $user->can('incidents.view');
    }

    public function manage(User $user): bool
    {
        return $user->can('incidents.loss_register');
    }
}
