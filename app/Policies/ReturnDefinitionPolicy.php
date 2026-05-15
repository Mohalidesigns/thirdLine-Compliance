<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Returns\Models\ReturnDefinition;

/**
 * Authorization policy for ReturnDefinition.
 *
 * super_admin bypasses all checks via Gate::before.
 */
class ReturnDefinitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('returns.view');
    }

    public function view(User $user, ReturnDefinition $definition): bool
    {
        return $user->can('returns.view');
    }

    public function create(User $user): bool
    {
        return $user->can('returns.manage');
    }

    public function update(User $user, ReturnDefinition $definition): bool
    {
        return $user->can('returns.manage');
    }

    public function delete(User $user, ReturnDefinition $definition): bool
    {
        return $user->can('returns.manage');
    }
}
