# Module: Dashboard

## BRD Modules Covered

- M18 — Compliance Dashboard & Analytics

## Purpose

The Dashboard module aggregates compliance posture data from across all other modules
and presents it in a role-appropriate view: Board/Executive (strategic heat-maps and
trend lines), Compliance Officer (operational status by area), and Analyst (item-level
queues and pending actions). All data surfaced is read-only aggregation — writes happen
in source modules. The module exposes widget data APIs consumed by the React workbench
frontend (A3) and caches expensive aggregations in the database cache store. Dashboard
refresh is bounded at ≤5 minutes stale per the NFR performance budget.

## Public Service Surface

TBD — to be defined by the Backend Domain Agent (A2) when implementing M18.

## Owned Tables

TBD — no owned tables; reads from other modules via defined service contracts only.
Expected: `dashboard_snapshots` (materialised roll-up cache).

## Dependencies

- `app/Concerns/BelongsToTenant` — multi-tenancy scope
- All domain modules (read-only aggregation) via their public service interfaces
- Database cache (CACHE_STORE=database) for widget snapshot caching
