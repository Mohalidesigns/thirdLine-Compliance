<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Training\Models\Training;

/**
 * Authorization policy for Training.
 *
 * super_admin bypasses every check via Gate::before.
 */
class TrainingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('training.view');
    }

    public function view(User $user, Training $training): bool
    {
        return $user->can('training.view');
    }

    public function create(User $user): bool
    {
        return $user->can('training.manage');
    }

    public function update(User $user, Training $training): bool
    {
        return $user->can('training.manage');
    }

    public function delete(User $user, Training $training): bool
    {
        return $user->can('training.manage');
    }
}
