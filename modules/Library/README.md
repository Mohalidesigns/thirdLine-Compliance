# Module: Library

## BRD Modules Covered

- M01 — Regulatory Instrument Library
- M03 — Compliance Calendar & Deadlines (obligation linkage)

## Purpose

The Library module is the canonical source of truth for every regulatory instrument
(acts, circulars, guidelines, directives) issued by CBN, NDIC, NFIU, SEC, NDPC, FIRS,
NAICOM, PenCom and adjacent bodies. It ingests instruments from the Day-1 data load,
maintains version chains, tracks effective/expiry dates, and exposes a structured API
that downstream modules (CRMP, Returns, Calendar) reference when mapping obligations to
controls or deadlines. The module also drives the obligation metadata that M03 uses to
populate the compliance calendar.

## Public Service Surface

TBD — to be defined by the Backend Domain Agent (A2) when implementing M01/M03.

## Owned Tables

TBD — expected: `instruments`, `instrument_versions`, `regulators`, `instrument_obligations`.

## Dependencies

- `app/Concerns/BelongsToTenant` — multi-tenancy scope
- `app/Concerns/EmitsAuditEvent` — audit trail hooks
- `app/Services/AuditWriter` — direct audit writes where trait is insufficient
- `maatwebsite/excel` — Day-1 import of 352 instruments from the toolkit XLSM
