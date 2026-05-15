<?php

declare(strict_types=1);

namespace Modules\Training\Services;

use App\Models\User;
use Modules\Training\Models\Certification;

class CertificationService
{
    /**
     * Record a new certification for a user.
     */
    public function recordCertification(User $user, array $data): Certification
    {
        return Certification::create(array_merge($data, [
            'user_id' => $user->id,
        ]));
    }

    /**
     * Scan all certifications and update their status field.
     *
     * - expired   : expires_at is in the past
     * - expiring  : expires_at is within 60 days
     * - active    : anything else (including no expiry date)
     *
     * Returns the count of rows whose status changed.
     */
    public function scanForExpiring(): int
    {
        $changed = 0;
        $expiringThreshold = now()->addDays(60);
        $today = now()->startOfDay();

        // Process in chunks to avoid loading everything into memory.
        Certification::withoutGlobalScopes()->chunk(200, function ($certifications) use (&$changed, $today, $expiringThreshold): void {
            foreach ($certifications as $cert) {
                if ($cert->expires_at === null) {
                    $newStatus = 'active';
                } elseif ($cert->expires_at->startOfDay()->lessThanOrEqualTo($today)) {
                    $newStatus = 'expired';
                } elseif ($cert->expires_at->startOfDay()->lessThanOrEqualTo($expiringThreshold)) {
                    $newStatus = 'expiring';
                } else {
                    $newStatus = 'active';
                }

                if ($cert->status !== $newStatus) {
                    $cert->update(['status' => $newStatus]);
                    $changed++;
                }
            }
        });

        return $changed;
    }
}
