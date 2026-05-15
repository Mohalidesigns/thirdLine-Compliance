<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Rcsa\Models\RiskAssessmentCycle;

/**
 * Authorization policy for RiskAssessmentCycle.
 *
 * super_admin bypasses every check via Gate::before.
 */
class RiskAssessmentCyclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cycles.view');
    }

    public function view(User $user, RiskAssessmentCycle $cycle): bool
    {
        return $user->can('cycles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cycles.create');
    }

    public function update(User $user, RiskAssessmentCycle $cycle): bool
    {
        return $user->can('cycles.update');
    }

    public function delete(User $user, RiskAssessmentCycle $cycle): bool
    {
        return $user->can('cycles.delete');
    }

    public function transition(User $user, RiskAssessmentCycle $cycle): bool
    {
        return $user->can('cycles.transition');
    }
}
