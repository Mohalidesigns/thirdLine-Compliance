<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Append-only, hash-chained audit event table.
 *
 * Every row's this_hash is computed by a BEFORE INSERT trigger using pgcrypto's
 * digest() function over the stable canonical fields. UPDATE and DELETE are
 * refused by separate triggers, making the table tamper-evident.
 *
 * Requires PostgreSQL 17 with the pgcrypto extension.
 */
return new class extends Migration
{
    public function up(): void
    {
        // This migration requires PostgreSQL + pgcrypto. When running under
        // SQLite (e.g., in the Pest Feature suite), skip and create a
        // simplified table without the hash-chain triggers.
        if (DB::getDriverName() !== 'pgsql') {
            $this->createSqliteStub();

            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto');

        DB::statement(<<<'SQL'
            CREATE TABLE audit_events (
                id            BIGSERIAL    PRIMARY KEY,
                tenant_id     BIGINT       NOT NULL,
                actor_id      BIGINT       NULL REFERENCES users(id) ON DELETE SET NULL,
                actor_type    TEXT         NULL,
                action        TEXT         NOT NULL,
                subject_type  TEXT         NULL,
                subject_id    BIGINT       NULL,
                context       JSONB        NOT NULL DEFAULT '{}',
                prev_hash     BYTEA        NULL,
                this_hash     BYTEA        NOT NULL DEFAULT '\x'::BYTEA,
                recorded_at   TIMESTAMPTZ  NOT NULL DEFAULT now()
            )
        SQL);

        // Composite indexes for the most common query patterns.
        DB::statement('CREATE INDEX idx_audit_events_tenant_recorded ON audit_events (tenant_id, recorded_at)');
        DB::statement('CREATE INDEX idx_audit_events_action ON audit_events (action)');
        DB::statement('CREATE INDEX idx_audit_events_subject ON audit_events (subject_type, subject_id)');

        // ------------------------------------------------------------------
        // BEFORE INSERT trigger: compute prev_hash and this_hash.
        //
        // canonical_row_bytes is a stable, deterministic concatenation of the
        // immutable columns. The order and separator must never change; if it
        // must change, a new migration adds a new hash column alongside.
        //
        // prev_hash is the this_hash of the last-inserted row for the same
        // tenant, or NULL for the first row (represented as '\x'::bytea in
        // the digest input so we get a consistent genesis hash).
        // ------------------------------------------------------------------
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION audit_events_before_insert()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            AS $$
            DECLARE
                v_prev_hash  BYTEA;
                v_canonical  BYTEA;
            BEGIN
                -- Fetch the previous row's hash for this tenant (most recent by id).
                SELECT this_hash
                INTO   v_prev_hash
                FROM   audit_events
                WHERE  tenant_id = NEW.tenant_id
                ORDER  BY id DESC
                LIMIT  1;

                -- First row for this tenant: use empty bytes as the genesis seed.
                IF v_prev_hash IS NULL THEN
                    v_prev_hash := '\x'::BYTEA;
                END IF;

                NEW.prev_hash := v_prev_hash;

                -- Build a stable canonical byte string from immutable fields.
                -- COALESCE ensures NULLable columns contribute a fixed-width
                -- sentinel rather than collapsing the concatenation.
                v_canonical :=
                    convert_to(NEW.tenant_id::TEXT, 'UTF8') ||
                    convert_to(COALESCE(NEW.actor_id::TEXT, 'NULL'), 'UTF8') ||
                    convert_to(COALESCE(NEW.actor_type, 'NULL'), 'UTF8') ||
                    convert_to(NEW.action, 'UTF8') ||
                    convert_to(COALESCE(NEW.subject_type, 'NULL'), 'UTF8') ||
                    convert_to(COALESCE(NEW.subject_id::TEXT, 'NULL'), 'UTF8') ||
                    convert_to(NEW.context::TEXT, 'UTF8') ||
                    convert_to(NEW.recorded_at::TEXT, 'UTF8');

                NEW.this_hash := digest(v_prev_hash || v_canonical, 'sha256');

                RETURN NEW;
            END;
            $$
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER audit_events_hash_chain
            BEFORE INSERT ON audit_events
            FOR EACH ROW
            EXECUTE FUNCTION audit_events_before_insert()
        SQL);

        // ------------------------------------------------------------------
        // BEFORE UPDATE trigger: refuse all mutations.
        // ------------------------------------------------------------------
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION audit_events_deny_update()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            AS $$
            BEGIN
                RAISE EXCEPTION
                    'audit_events is append-only: UPDATE is not permitted (row id=%). '
                    'Contact your compliance officer if you believe this is an error.',
                    OLD.id;
                RETURN NULL;
            END;
            $$
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER audit_events_no_update
            BEFORE UPDATE ON audit_events
            FOR EACH ROW
            EXECUTE FUNCTION audit_events_deny_update()
        SQL);

        // ------------------------------------------------------------------
        // BEFORE DELETE trigger: refuse all deletions.
        // ------------------------------------------------------------------
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION audit_events_deny_delete()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            AS $$
            BEGIN
                RAISE EXCEPTION
                    'audit_events is append-only: DELETE is not permitted (row id=%). '
                    'Contact your compliance officer if you believe this is an error.',
                    OLD.id;
                RETURN NULL;
            END;
            $$
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER audit_events_no_delete
            BEFORE DELETE ON audit_events
            FOR EACH ROW
            EXECUTE FUNCTION audit_events_deny_delete()
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            DB::statement('DROP TABLE IF EXISTS audit_events');

            return;
        }

        // Drop triggers and functions before the table.
        DB::statement('DROP TRIGGER IF EXISTS audit_events_no_delete ON audit_events');
        DB::statement('DROP TRIGGER IF EXISTS audit_events_no_update ON audit_events');
        DB::statement('DROP TRIGGER IF EXISTS audit_events_hash_chain ON audit_events');
        DB::statement('DROP FUNCTION IF EXISTS audit_events_deny_delete()');
        DB::statement('DROP FUNCTION IF EXISTS audit_events_deny_update()');
        DB::statement('DROP FUNCTION IF EXISTS audit_events_before_insert()');
        DB::statement('DROP TABLE IF EXISTS audit_events');
    }

    /**
     * Create a simplified audit_events table for SQLite (test environments).
     *
     * This stub has the same columns but no Postgres triggers — the hash
     * columns are nullable so the schema doesn't break SQLite tests that
     * don't exercise the hash-chain functionality.
     */
    private function createSqliteStub(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS audit_events (
                id           INTEGER      PRIMARY KEY AUTOINCREMENT,
                tenant_id    INTEGER      NOT NULL,
                actor_id     INTEGER      NULL,
                actor_type   TEXT         NULL,
                action       TEXT         NOT NULL,
                subject_type TEXT         NULL,
                subject_id   INTEGER      NULL,
                context      TEXT         NOT NULL DEFAULT '{}',
                prev_hash    BLOB         NULL,
                this_hash    BLOB         NULL,
                recorded_at  TEXT         NOT NULL DEFAULT (datetime('now'))
            )
        SQL);
    }
};
