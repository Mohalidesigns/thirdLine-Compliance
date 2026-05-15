<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Incident\Models\IncidentNotification;

class IncidentNotificationPolicy
{
    public function view(User $user, IncidentNotification $notification): bool
    {
        return $user->can('incidents.view');
    }

    public function record(User $user, IncidentNotification $notification): bool
    {
        return $user->can('incidents.notify');
    }
}
