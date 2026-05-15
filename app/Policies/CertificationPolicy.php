<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Training\Models\Certification;

/**
 * Authorization policy for Certification.
 *
 * super_admin bypasses every check via Gate::before.
 */
class CertificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('certifications.view') || $user->can('certifications.manage');
    }

    /**
     * view: the certification owner can always view their own.
     * Users with certifications.manage can view any certification.
     * Users with only certifications.view can view their own only (not others').
     */
    public function view(User $user, Certification $certification): bool
    {
        if ($certification->user_id === $user->id) {
            return true;
        }

        return $user->can('certifications.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('certifications.manage');
    }

    public function update(User $user, Certification $certification): bool
    {
        return $user->can('certifications.manage');
    }

    public function delete(User $user, Certification $certification): bool
    {
        return $user->can('certifications.manage');
    }
}
