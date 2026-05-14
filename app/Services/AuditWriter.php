<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Synchronous audit-event recorder.
 *
 * Writes one row to audit_events per call. The write is intentionally
 * synchronous and throws on failure so that the calling transaction
 * can roll back — a successful business operation with no audit trail is
 * worse than a failed one.
 *
 * Do NOT queue this write. If latency becomes a concern, the correct fix
 * is to tune the Postgres connection pool, not to make the audit trail
 * asynchronous and therefore losable.
 */
class AuditWriter
{
    /**
     * Record an audit event.
     *
     * @param  string  $action  Dot-namespaced action identifier, e.g. 'instrument.created'.
     * @param  Model|null  $subject  The Eloquent model that was acted upon, if applicable.
     * @param  array<string, mixed>  $context  Additional structured context (free-form JSON).
     *
     * @throws RuntimeException When the insert fails for any reason.
     */
    public function record(string $action, ?Model $subject = null, array $context = []): void
    {
        $actorId = Auth::id();
        $actorType = $actorId !== null ? 'user' : 'system';

        $inserted = DB::table('audit_events')->insert([
            'tenant_id'    => $this->resolveTenantId(),
            'actor_id'     => $actorId,
            'actor_type'   => $actorType,
            'action'       => $action,
            'subject_type' => $subject !== null ? $subject->getMorphClass() : null,
            'subject_id'   => $subject?->getKey(),
            'context'      => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'recorded_at'  => now()->toIso8601ZuluString(),
        ]);

        if (! $inserted) {
            throw new RuntimeException(
                "AuditWriter: failed to insert audit event for action [{$action}]."
            );
        }
    }

    /**
     * Resolve the current tenant ID.
     *
     * MVP stub returns 1. Replace with a TenantResolver service before
     * multi-tenant go-live (matches BelongsToTenant::currentTenantId).
     */
    private function resolveTenantId(): int
    {
        return (int) (app()->bound('current.tenant_id')
            ? app('current.tenant_id')
            : 1);
    }
}
