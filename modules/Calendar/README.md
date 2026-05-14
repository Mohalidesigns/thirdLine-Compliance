# Module: Calendar

## BRD Modules Covered

- M17 — Compliance Calendar & Deadline Management

## Purpose

The Calendar module provides a structured view of all time-bound compliance obligations:
regulatory return due dates, control review cycles, licence renewals, reporting windows,
and internal audit schedules. It ingests deadlines from the Library module (M01) and the
Returns module (M10), applies institution-specific configurations (e.g., working-day
calendars, public holiday overrides), and generates upcoming-deadline notifications that
feed the Dashboard (M18) and the Notify cross-cutting module. The module supports manual
deadline entry for non-instrument-driven obligations and enforces maker-checker on
deadline modifications.

## Public Service Surface

TBD — to be defined by the Backend Domain Agent (A2) when implementing M17.

## Owned Tables

TBD — expected: `compliance_deadlines`, `calendar_entries`, `holiday_calendars`, `deadline_notifications`.

## Dependencies

- `app/Concerns/BelongsToTenant` — multi-tenancy scope
- `app/Concerns/EmitsAuditEvent` — audit trail hooks
- Modules/Library — sources obligation deadlines from instrument metadata
- Modules/Dashboard — pushes upcoming-deadline widget data
