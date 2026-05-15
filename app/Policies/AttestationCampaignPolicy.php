<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Training\Models\AttestationCampaign;

/**
 * Authorization policy for AttestationCampaign.
 *
 * super_admin bypasses every check via Gate::before.
 */
class AttestationCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attestations.manage') || $user->can('attestations.sign');
    }

    public function view(User $user, AttestationCampaign $campaign): bool
    {
        return $user->can('attestations.manage') || $user->can('attestations.sign');
    }

    public function create(User $user): bool
    {
        return $user->can('attestations.manage');
    }

    public function update(User $user, AttestationCampaign $campaign): bool
    {
        return $user->can('attestations.manage');
    }

    public function delete(User $user, AttestationCampaign $campaign): bool
    {
        return $user->can('attestations.manage');
    }

    /**
     * sign: anyone with attestations.sign can sign campaigns assigned to them.
     * The controller further checks campaign status and user role membership.
     */
    public function sign(User $user, ?AttestationCampaign $campaign = null): bool
    {
        return $user->can('attestations.sign');
    }
}
