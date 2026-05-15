<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Controls\Models\Control;

/**
 * Authorization policy for Control.
 *
 * super_admin bypasses every check via Gate::before.
 */
class ControlPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('controls.view');
    }

    public function view(User $user, Control $control): bool
    {
        return $user->can('controls.view');
    }

    public function create(User $user): bool
    {
        return $user->can('controls.create');
    }

    public function update(User $user, Control $control): bool
    {
        return $user->can('controls.update');
    }

    public function delete(User $user, Control $control): bool
    {
        return $user->can('controls.delete');
    }

    public function test(User $user, Control $control): bool
    {
        return $user->can('controls.test');
    }
}
