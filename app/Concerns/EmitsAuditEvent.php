<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Services\AuditWriter;
use Illuminate\Database\Eloquent\Model;

/**
 * Automatically emits audit events on state-changing Eloquent model events.
 *
 * Hooks: created, updated (separate events — not saved, which cannot reliably
 * distinguish create vs update because wasRecentlyCreated stays true on the
 * in-memory instance after Instrument::create() even on subsequent update()
 * calls), deleting (soft delete), forceDeleted (hard delete).
 *
 * The write is synchronous and will throw on failure, so the calling
 * transaction rolls back rather than succeeding without an audit trail.
 */
trait EmitsAuditEvent
{
    public static function bootEmitsAuditEvent(): void
    {
        static::created(function (Model $model): void {
            app(AuditWriter::class)->record(
                action: static::auditActionPrefix().'.created',
                subject: $model,
                context: ['changes' => $model->getChanges()],
            );
        });

        static::updated(function (Model $model): void {
            app(AuditWriter::class)->record(
                action: static::auditActionPrefix().'.updated',
                subject: $model,
                context: ['changes' => $model->getChanges()],
            );
        });

        static::deleting(function (Model $model): void {
            app(AuditWriter::class)->record(
                action: static::auditActionPrefix().'.deleted',
                subject: $model,
            );
        });

        static::forceDeleted(function (Model $model): void {
            app(AuditWriter::class)->record(
                action: static::auditActionPrefix().'.force_deleted',
                subject: $model,
            );
        });
    }

    /**
     * Override in a model to customise the action prefix.
     * Defaults to the snake_case of the model class name.
     *
     * Example: InstrumentVersion → 'instrument_version'
     */
    protected static function auditActionPrefix(): string
    {
        return str(class_basename(static::class))->snake()->toString();
    }
}
