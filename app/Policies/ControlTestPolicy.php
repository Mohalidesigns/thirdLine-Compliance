<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Controls\Models\ControlTest;

/**
 * Authorization policy for ControlTest.
 *
 * Recording a test requires controls.test; viewing requires controls.view.
 * super_admin bypasses every check via Gate::before.
 */
class ControlTestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('controls.view');
    }

    public function view(User $user, ControlTest $test): bool
    {
        return $user->can('controls.view');
    }

    public function create(User $user): bool
    {
        return $user->can('controls.test');
    }

    public function update(User $user, ControlTest $test): bool
    {
        // Control tests are immutable once recorded; no update gate.
        return false;
    }

    public function delete(User $user, ControlTest $test): bool
    {
        return $user->can('controls.delete');
    }
}
