<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Policy\Models\Policy;

/**
 * Authorization policy for the Policy domain model.
 *
 * Note: super_admin bypasses every method here via Gate::before in
 * AppServiceProvider — no super_admin clause is needed per method.
 *
 * policies.update for policy_owner is intentionally restricted at this layer:
 * the permission is granted in the seeder, but actual updates are permitted
 * only when state === 'draft' AND the user created the policy (created_by).
 * Because the policies table has no created_by column at this stage, we fall
 * back to: policy_owner may update any draft.  When a created_by column is
 * added, replace the check below with:
 *   $policy->created_by === $user->id
 */
class PolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('policies.view');
    }

    public function view(User $user, Policy $policy): bool
    {
        return $user->can('policies.view');
    }

    public function create(User $user): bool
    {
        return $user->can('policies.create');
    }

    /**
     * Update permission with extra policy_owner restriction:
     * policy_owner may only update policies in 'draft' state.
     *
     * When a created_by column exists, add:
     *   if ($user->hasRole('policy_owner')) {
     *       return $policy->created_by === $user->id && $policy->state::$name === 'draft';
     *   }
     */
    public function update(User $user, Policy $policy): bool
    {
        if (! $user->can('policies.update')) {
            return false;
        }

        // policy_owner is restricted to drafts only (separation of duties).
        if ($user->hasRole('policy_owner')) {
            return $policy->state::$name === 'draft';
        }

        return true;
    }

    public function delete(User $user, Policy $policy): bool
    {
        return $user->can('policies.delete');
    }

    // ── Transition-specific gates ─────────────────────────────────────────────

    public function submitForReview(User $user, Policy $policy): bool
    {
        return $user->can('policies.transition.submit_for_review');
    }

    public function approve(User $user, Policy $policy): bool
    {
        return $user->can('policies.transition.approve');
    }

    public function publish(User $user, Policy $policy): bool
    {
        return $user->can('policies.transition.publish');
    }

    public function force(User $user, Policy $policy): bool
    {
        return $user->can('policies.transition.force');
    }

    /**
     * Generic transition gate used by PoliciesController::transition().
     * Maps the requested target state to the fine-grained permission.
     */
    public function transition(User $user, Policy $policy, string $to = ''): bool
    {
        return match ($to) {
            'in_review' => $this->submitForReview($user, $policy),
            'approved' => $this->approve($user, $policy),
            'published' => $this->publish($user, $policy),
            'in_force' => $this->force($user, $policy),
            // draft (request-changes), under_review, superseded, etc. — require approve permission.
            default => $user->can('policies.transition.approve'),
        };
    }
}
