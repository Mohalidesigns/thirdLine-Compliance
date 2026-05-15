<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Incident\Models\Incident;

/**
 * Authorization policy for Incident.
 *
 * super_admin bypasses every check via Gate::before.
 */
class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('incidents.view');
    }

    public function view(User $user, Incident $incident): bool
    {
        return $user->can('incidents.view');
    }

    public function create(User $user): bool
    {
        return $user->can('incidents.create');
    }

    public function update(User $user, Incident $incident): bool
    {
        return $user->can('incidents.update');
    }

    public function delete(User $user, Incident $incident): bool
    {
        return $user->can('incidents.delete');
    }

    /**
     * Record regulator notifications.
     */
    public function notify(User $user, Incident $incident): bool
    {
        return $user->can('incidents.notify');
    }

    /**
     * Attach evidence items.
     */
    public function attachEvidence(User $user, Incident $incident): bool
    {
        return $user->can('incidents.attach_evidence');
    }

    /**
     * Close an incident — requires update permission AND evidence present.
     */
    public function close(User $user, Incident $incident): bool
    {
        return $user->can('incidents.update') && $incident->closure_evidence_count > 0;
    }

    /**
     * Record operational loss events.
     */
    public function recordLoss(User $user, Incident $incident): bool
    {
        return $user->can('incidents.loss_register');
    }
}
