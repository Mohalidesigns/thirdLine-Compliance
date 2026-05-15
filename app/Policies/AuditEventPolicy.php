<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Audit\Models\AuditEvent;

/**
 * Authorization policy for AuditEvent.
 *
 * The audit log is read-only. Create/update/delete are always denied.
 * super_admin bypasses every check via Gate::before.
 */
class AuditEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('audit.view');
    }

    public function view(User $user, AuditEvent $event): bool
    {
        return $user->can('audit.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditEvent $event): bool
    {
        return false;
    }

    public function delete(User $user, AuditEvent $event): bool
    {
        return false;
    }
}
