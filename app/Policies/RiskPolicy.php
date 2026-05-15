<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Rcsa\Models\Risk;

/**
 * Authorization policy for Risk.
 *
 * risk_owner may update and score risks, subject to the additional constraint
 * that the cycle must be in 'data_capture' or 'scoring' state.  The permission
 * (risks.update / risks.score) is granted unconditionally in the seeder; this
 * policy enforces the cycle-state guard for risk_owner.
 *
 * super_admin bypasses every check via Gate::before.
 */
class RiskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('risks.view');
    }

    public function view(User $user, Risk $risk): bool
    {
        return $user->can('risks.view');
    }

    public function create(User $user): bool
    {
        return $user->can('risks.create');
    }

    /**
     * risk_owner can update own risks only while the cycle is in data_capture
     * or scoring.  Other roles with risks.update have no cycle-state restriction.
     */
    public function update(User $user, Risk $risk): bool
    {
        if (! $user->can('risks.update')) {
            return false;
        }

        if ($user->hasRole('risk_owner')) {
            $risk->loadMissing('cycle');
            $cycleState = $risk->cycle?->state::$name ?? 'planning';

            return in_array($cycleState, ['data_capture', 'scoring'], true);
        }

        return true;
    }

    /**
     * Mirrors the cycle-state restriction for scoring.
     */
    public function score(User $user, Risk $risk): bool
    {
        if (! $user->can('risks.score')) {
            return false;
        }

        if ($user->hasRole('risk_owner')) {
            $risk->loadMissing('cycle');
            $cycleState = $risk->cycle?->state::$name ?? 'planning';

            return in_array($cycleState, ['data_capture', 'scoring'], true);
        }

        return true;
    }

    public function delete(User $user, Risk $risk): bool
    {
        return $user->can('risks.delete');
    }
}
