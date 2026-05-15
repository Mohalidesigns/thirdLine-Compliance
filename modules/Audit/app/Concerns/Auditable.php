<?php

declare(strict_types=1);

namespace Modules\Audit\Concerns;

use App\Concerns\EmitsAuditEvent;
use App\Services\AuditWriter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Models\AuditEvent;

/**
 * Eloquent trait that automatically records audit events when a model is
 * created, updated, deleted, or restored.
 *
 * The trait delegates writes to AuditWriter (which writes to the existing
 * audit_events table). Change diffs are stored inside the context JSON under
 * the key "changes": { "before": {...}, "after": {...} }.
 *
 * Usage
 * -----
 *   use Modules\Audit\Concerns\Auditable;
 *
 *   class Control extends Model {
 *       use Auditable;
 *   }
 *
 * Override protected $auditExcept on the model to add extra excluded fields:
 *
 *   protected array $auditExcept = ['internal_notes'];
 *
 * Domain-level events (state machines, acknowledgements, etc.) can be logged
 * with the escape-hatch method:
 *
 *   $model->recordAudit('state_transitioned', ['before' => [...], 'after' => [...]]);
 *
 * Note on the existing EmitsAuditEvent trait
 * ------------------------------------------
 * Several models already use App\Concerns\EmitsAuditEvent, which fires cruder
 * action strings like "control.created". Auditable fires more granular strings:
 * "created", "updated", "deleted", "restored". Models should use one or the
 * other, not both, to avoid duplicate rows.
 */
trait Auditable
{
    /**
     * Fields that are NEVER included in changes output, regardless of model.
     */
    private static array $defaultAuditExclude = [
        'password',
        'remember_token',
        'api_token',
        '_password',
    ];

    /**
     * Register Eloquent event listeners via the boot hook convention.
     *
     * When EmitsAuditEvent is also used on the model, the CRUD event listeners
     * are intentionally skipped to avoid writing duplicate audit rows.
     * The recordAudit() method is always available regardless.
     */
    public static function bootAuditable(): void
    {
        // Guard: do not audit AuditEvent itself (infinite recursion).
        if (static::class === AuditEvent::class) {
            return;
        }

        // If the model already uses EmitsAuditEvent, skip the CRUD hooks.
        // EmitsAuditEvent fires on the same Eloquent events and would produce
        // duplicate rows. The recordAudit() method below is always available.
        if (in_array(EmitsAuditEvent::class, class_uses_recursive(static::class), true)) {
            return;
        }

        static::created(function (Model $model): void {
            $after = static::filterAuditFields($model, $model->getAttributes());

            static::writeAuditEvent(
                model: $model,
                action: 'created',
                changes: ['after' => $after],
            );
        });

        static::updated(function (Model $model): void {
            $dirty = $model->getDirty();
            $before = [];
            $after = [];

            foreach (static::filterAuditFields($model, $dirty) as $key => $newValue) {
                $before[$key] = $model->getOriginal($key);
                $after[$key] = $newValue;
            }

            if (empty($after)) {
                // Nothing changed after exclusion — skip write.
                return;
            }

            static::writeAuditEvent(
                model: $model,
                action: 'updated',
                changes: ['before' => $before, 'after' => $after],
            );
        });

        static::deleting(function (Model $model): void {
            static::writeAuditEvent(model: $model, action: 'deleted');
        });

        // Only wire restored if the model uses SoftDeletes.
        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(function (Model $model): void {
                static::writeAuditEvent(model: $model, action: 'restored');
            });
        }
    }

    // ------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------

    /**
     * Record an explicit domain-level audit event (e.g., state_transitioned,
     * acknowledged). Call this from services or controllers for semantic events
     * that go beyond CRUD.
     *
     * @param  string  $action  A verb describing the domain action.
     * @param  array<string, mixed>|null  $changes  Optional before/after diff.
     * @param  array<string, mixed>|null  $context  Additional context merged into the row.
     */
    public function recordAudit(
        string $action,
        ?array $changes = null,
        ?array $context = null,
    ): void {
        static::writeAuditEvent(
            model: $this,
            action: $action,
            changes: $changes,
            extraContext: $context ?? [],
        );
    }

    // ------------------------------------------------------------------
    // Internal helpers (private — not part of the public API)
    // ------------------------------------------------------------------

    /**
     * Delegate the write to AuditWriter, composing the context payload.
     *
     * @param  array<string, mixed>|null  $changes
     * @param  array<string, mixed>  $extraContext
     */
    private static function writeAuditEvent(
        Model $model,
        string $action,
        ?array $changes = null,
        array $extraContext = [],
    ): void {
        $context = $extraContext;

        if ($changes !== null) {
            $context['changes'] = $changes;
        }

        // Append request context when a request is available.
        if (app()->bound('request') && ($request = request()) !== null) {
            try {
                $context['ip'] = $request->ip();
                $context['user_agent'] = $request->userAgent();
            } catch (\Throwable) {
                // Console commands have no real request; suppress silently.
            }
        }

        app(AuditWriter::class)->record(
            action: $action,
            subject: $model,
            context: $context,
        );
    }

    /**
     * Filter an attribute map to remove excluded fields.
     *
     * The exclusion list combines the default list with the model's optional
     * $auditExcept property. Wildcard patterns (*_token, *_secret) are matched
     * by fnmatch.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private static function filterAuditFields(Model $model, array $attributes): array
    {
        $exclude = array_merge(
            static::$defaultAuditExclude,
            $model->auditExcept ?? [],
        );

        $wildcardPatterns = ['*_token', '*_secret'];

        return array_filter(
            $attributes,
            static function (mixed $value, string $key) use ($exclude, $wildcardPatterns): bool {
                if (in_array($key, $exclude, true)) {
                    return false;
                }

                foreach ($wildcardPatterns as $pattern) {
                    if (fnmatch($pattern, $key)) {
                        return false;
                    }
                }

                return true;
            },
            ARRAY_FILTER_USE_BOTH,
        );
    }
}
