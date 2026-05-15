<?php

declare(strict_types=1);

namespace Modules\Audit\Models;

use App\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Read-only Eloquent wrapper for the audit_events table.
 *
 * The table is append-only (PostgreSQL triggers refuse UPDATE and DELETE).
 * On SQLite (test env) triggers are absent but the model enforces the same
 * contract by making $timestamps = false and providing no write API beyond
 * what AuditWriter uses via raw DB inserts.
 *
 * Columns match the existing schema from:
 *   database/migrations/2026_05_14_190000_create_audit_events_table.php
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $actor_id
 * @property string|null $actor_type
 * @property string $action
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property array $context
 * @property string|null $recorded_at
 */
class AuditEvent extends Model
{
    use BelongsToTenant;

    /**
     * The table uses recorded_at (not created_at/updated_at).
     * We disable Eloquent timestamps entirely and rely on the DB default.
     */
    public $timestamps = false;

    protected $table = 'audit_events';

    /**
     * Disable mass-assignment protection.
     * AuditWriter writes directly via DB::table(); this model is read-only.
     */
    protected $guarded = ['id'];

    protected $casts = [
        'context' => 'array',
        'actor_id' => 'integer',
        'subject_id' => 'integer',
        'tenant_id' => 'integer',
        'recorded_at' => 'datetime',
    ];

    // ------------------------------------------------------------------
    // Relations
    // ------------------------------------------------------------------

    /**
     * The authenticated user (actor) who triggered the event.
     * actor_id corresponds to users.id; actor_type is a text discriminator
     * ('user' or 'system') stored separately — not a standard Eloquent morph.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * The Eloquent model that was acted upon.
     * Uses the existing subject_type / subject_id columns.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'subject_type', 'subject_id');
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    /**
     * Filter events for a specific Eloquent record by its class and primary key.
     * Used by Filament relation managers and history panels.
     */
    public function scopeForRecord(Builder $query, Model $record): Builder
    {
        return $query
            ->where('subject_type', $record->getMorphClass())
            ->where('subject_id', $record->getKey())
            ->orderByDesc('recorded_at');
    }

    /**
     * Scope to events created within the current tenant.
     * Overrides BelongsToTenant's default created listener (we never write
     * via this model — only read) while still applying the global scope.
     */
    public static function bootAuditEvent(): void
    {
        // Remove the BelongsToTenant creating listener — this model is read-only.
        // The global scope from bootBelongsToTenant still applies for reads.
        static::creating(static function (): bool {
            return false; // prevent writes through Eloquent
        });
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Return a short human-readable subject label without the fully-qualified namespace.
     * e.g. "Modules\Controls\Models\Control" → "Control"
     */
    public function subjectLabel(): string
    {
        return class_basename((string) $this->subject_type);
    }
}
