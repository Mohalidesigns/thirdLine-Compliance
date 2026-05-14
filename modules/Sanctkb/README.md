# Module: Sanctkb

## BRD Modules Covered

- M28 — Sanctions & PEP Knowledge Base

## Purpose

The Sanctkb module maintains the institution's local copy of global sanctions lists
(OFAC SDN, UN Consolidated, EU Financial Sanctions, HMT, local NFIU/CBN lists) and
PEP (Politically Exposed Person) registries. It provides a fast, tenant-scoped lookup
API consumed by the Screening module (M07) and the Customer onboarding module (M06).
Sanctions lists are refreshed on a configurable schedule and every update is versioned
and audit-trailed. The module also stores institution-level whitelists/false-positive
records with a maker-checker workflow to prevent unauthorised overrides.

## Public Service Surface

TBD — to be defined by the Backend Domain Agent (A2) when implementing M28.

## Owned Tables

TBD — expected: `sanction_lists`, `sanction_entries`, `pep_entries`, `list_refreshes`, `false_positives`.

## Dependencies

- `app/Concerns/BelongsToTenant` — multi-tenancy scope
- `app/Concerns/EmitsAuditEvent` — audit trail hooks
- Modules/Screening — consumes Sanctkb's lookup API
