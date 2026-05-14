# Module: Audit

## BRD Modules Covered

- M19 — Audit Trail & Evidence Management (cross-cutting)

## Purpose

The Audit module is a thin wrapper around the `app/Services/AuditWriter` cross-cutting
service and the `audit_events` hash-chained append-only table. It owns the Artisan
command `atheris:audit:seal-day` (daily Merkle root computation, to be wired to HSM
signing in a later phase), the audit event query/export service, and the access-control
policies governing who can read audit entries (read-only, no delete, no update). The
module also provides the Inertia pages for audit log browsing (to be built by A3) and
the Filament resource for admin-level inspection (to be built by A4). This module does
NOT write audit events directly — all writes go through `AuditWriter` which is in
`app/Services/`, keeping the cross-cutting concern out of the module layer.

## Public Service Surface

TBD — expected: `AuditQueryService` (search, filter, export), `AuditSealService` (daily
Merkle sealer), `AuditChainVerifier` (integrity check command).

## Owned Tables

- `audit_events` (defined in `database/migrations/...create_audit_events_table.php`)
  This table is created by a global migration, not a module migration, because it is
  a cross-cutting infrastructure table required before any module can write to it.

## Dependencies

- `app/Services/AuditWriter` — all writes go through this service
- pgcrypto (PostgreSQL extension) — hash computation in DB trigger
- spatie/laravel-permission — governs who can query audit logs
