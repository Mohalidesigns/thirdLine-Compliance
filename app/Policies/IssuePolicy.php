<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Controls\Models\Issue;

/**
 * Authorization policy for Issue.
 *
 * super_admin bypasses every check via Gate::before.
 */
class IssuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('issues.view');
    }

    public function view(User $user, Issue $issue): bool
    {
        return $user->can('issues.view');
    }

    public function create(User $user): bool
    {
        return $user->can('issues.create');
    }

    public function update(User $user, Issue $issue): bool
    {
        return $user->can('issues.update');
    }

    public function delete(User $user, Issue $issue): bool
    {
        return $user->can('issues.delete');
    }

    /**
     * Transitioning an issue's status requires issues.transition.
     * The allowed state-machine transitions are still enforced in
     * IssuesController::validateStatusTransition().
     */
    public function transition(User $user, Issue $issue): bool
    {
        return $user->can('issues.transition');
    }
}
