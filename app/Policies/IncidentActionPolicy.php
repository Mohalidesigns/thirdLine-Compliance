<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Incident\Models\IncidentAction;

class IncidentActionPolicy
{
    public function view(User $user, IncidentAction $action): bool
    {
        return $user->can('incidents.view');
    }

    public function create(User $user): bool
    {
        return $user->can('incidents.update');
    }

    public function update(User $user, IncidentAction $action): bool
    {
        return $user->can('incidents.update');
    }
}
