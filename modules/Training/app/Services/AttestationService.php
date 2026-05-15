<?php

declare(strict_types=1);

namespace Modules\Training\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Training\Models\AttestationCampaign;
use Modules\Training\Models\AttestationRecord;

class AttestationService
{
    /**
     * Record a signature for a campaign. Idempotent — if the record already exists, return it.
     */
    public function signCampaign(AttestationCampaign $campaign, User $user, Request $request): AttestationRecord
    {
        $existing = AttestationRecord::withoutGlobalScopes()
            ->where('campaign_id', $campaign->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return AttestationRecord::create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'signed_at' => now(),
            'ip' => $request->ip() ?? '0.0.0.0',
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Return active campaigns that:
     *   1. Require the user's role (mandatory_for_roles intersects user roles).
     *   2. The user has NOT yet signed.
     */
    public function pendingForUser(User $user): Collection
    {
        $userRoles = $user->getRoleNames()->toArray();

        $activeCampaigns = AttestationCampaign::where('status', 'active')->get();

        $pending = [];

        foreach ($activeCampaigns as $campaign) {
            $mandatoryForRoles = $campaign->mandatory_for_roles ?? [];

            // Empty mandatory_for_roles means no one is required (skip).
            if (empty($mandatoryForRoles)) {
                continue;
            }

            // Check if user's roles intersect the required roles.
            if (count(array_intersect($userRoles, $mandatoryForRoles)) === 0) {
                continue;
            }

            // Check if already signed.
            if ($campaign->hasSigned($user)) {
                continue;
            }

            $pending[] = $campaign;
        }

        return collect($pending);
    }
}
