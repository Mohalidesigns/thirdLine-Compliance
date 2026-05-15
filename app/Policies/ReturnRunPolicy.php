<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Returns\Models\ReturnRun;

/**
 * Authorization policy for ReturnRun.
 *
 * Enforces the maker/checker/approver separation-of-duties rules in
 * addition to RBAC permission checks.
 *
 * super_admin bypasses all checks via Gate::before.
 */
class ReturnRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('returns.view');
    }

    public function view(User $user, ReturnRun $run): bool
    {
        return $user->can('returns.view');
    }

    /**
     * Maker step: user must have returns.maker permission.
     */
    public function submitForReview(User $user, ReturnRun $run): bool
    {
        return $user->can('returns.maker');
    }

    /**
     * Checker step: user must have returns.checker AND not be the maker.
     */
    public function approveAsChecker(User $user, ReturnRun $run): bool
    {
        return $user->can('returns.checker')
            && $user->id !== $run->maker_id;
    }

    /**
     * Approver step: user must have returns.approver AND not be maker or checker.
     */
    public function signOff(User $user, ReturnRun $run): bool
    {
        return $user->can('returns.approver')
            && $user->id !== $run->maker_id
            && $user->id !== $run->checker_id;
    }

    /**
     * Acknowledge: user must have returns.acknowledge permission.
     */
    public function recordAcknowledgement(User $user, ReturnRun $run): bool
    {
        return $user->can('returns.acknowledge');
    }
}
