<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Training\Models\TrainingEnrollment;

/**
 * Authorization policy for TrainingEnrollment.
 *
 * super_admin bypasses every check via Gate::before.
 */
class TrainingEnrollmentPolicy
{
    /**
     * viewAny: anyone with training.view can see the enrollment list.
     * Used for the admin listing. The user-facing "my training" index
     * is further filtered to own enrollments in the controller.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('training.view');
    }

    /**
     * view: the enrollment owner can view their own enrollment,
     * or any user with training.view + training.manage can view any.
     */
    public function view(User $user, TrainingEnrollment $enrollment): bool
    {
        if ($enrollment->user_id === $user->id) {
            return true;
        }

        return $user->can('training.view') && $user->can('training.manage');
    }

    /**
     * update: requires training.manage (for admin overrides).
     */
    public function update(User $user, TrainingEnrollment $enrollment): bool
    {
        return $user->can('training.manage');
    }

    /**
     * complete: the owner of the enrollment can complete it if they have training.complete.
     */
    public function complete(User $user, TrainingEnrollment $enrollment): bool
    {
        return $enrollment->user_id === $user->id && $user->can('training.complete');
    }

    /**
     * start: same as complete — the enrollment owner with training.complete can start.
     */
    public function start(User $user, TrainingEnrollment $enrollment): bool
    {
        return $enrollment->user_id === $user->id && $user->can('training.complete');
    }

    /**
     * exempt: requires training.manage.
     */
    public function exempt(User $user, TrainingEnrollment $enrollment): bool
    {
        return $user->can('training.manage');
    }
}
