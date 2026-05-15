<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Returns\Models\ReturnApproval;

/**
 * Authorization policy for ReturnApproval (approval history).
 *
 * Approval records are read-only audit history — no user can create,
 * update, or delete them directly; that happens through ReturnRun workflow
 * actions (service layer).
 *
 * super_admin bypasses all checks via Gate::before.
 */
class ReturnApprovalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('returns.view');
    }

    public function view(User $user, ReturnApproval $approval): bool
    {
        return $user->can('returns.view');
    }
}
