# Policy Module (M02)

Policy & Procedure Management for the Atheris Compliance platform.

## Overview

Manages the full lifecycle of compliance policies from draft through to in-force, with immutable version history, PDF rendering, and staff acknowledgement tracking.

## Policy body format

`body` is stored as plain text or Markdown. The PDF renderer uses `nl2br(e($body))` for HTML output — no rich-text parsing. If richer formatting is needed in a later phase, migrate `body` to a parsed Markdown renderer or a dedicated rich-text format.

## State machine

```
Draft → InReview → Approved → Published → InForce → UnderReview → Superseded
               ↑                           ↓
               └─── request-changes ───────┘
```

| Transition | From | To | Requires note |
|---|---|---|---|
| submit-for-review | Draft | InReview | No |
| approve | InReview | Approved | No |
| request-changes | InReview | Draft | Yes |
| publish | Approved | Published | No |
| mark-in-force | Published | InForce | No |
| start-review | InForce | UnderReview | No |
| reaffirm | UnderReview | InForce | No |
| supersede | UnderReview | Superseded | Yes |

Automated transitions run via `php artisan policy:advance-states` (scheduled daily):
- `Published → InForce` when `effective_date <= today`
- `InForce → UnderReview` when `next_review_date <= today + 60 days`

## PDF rendering

`RenderPolicyPdfJob` dispatched on publish. Renders `resources/views/policy/pdf.blade.php` with DomPDF. Output stored at `storage/app/policies/{id}/v{version}.pdf`. The path is recorded in `policies.published_pdf_path`. PDF download is gated to `in_force` and `superseded` states only.

## Environment variables

No new variables required. PDFs use local storage (`storage/app/...`) — no S3 required for MVP.

## Routes

| Name | Method | Path |
|---|---|---|
| `policies.index` | GET | /policies |
| `policies.create` | GET | /policies/create |
| `policies.store` | POST | /policies |
| `policies.show` | GET | /policies/{policy} |
| `policies.edit` | GET | /policies/{policy}/edit |
| `policies.update` | PUT | /policies/{policy} |
| `policies.destroy` | DELETE | /policies/{policy} |
| `policies.transition` | POST | /policies/{policy}/transition |
| `policies.acknowledge` | POST | /policies/{policy}/acknowledge |
| `policies.download` | GET | /policies/{policy}/download |

## Filament admin

`PolicyResource` is registered in `AdminPanelProvider`. State transitions are surfaced as table-row actions gated by the current state.

## Seeder

`PolicySeeder` seeds 8 Nigerian-bank-flavoured policies in various states.

## Tests

9 Pest tests under `tests/Feature/Policy/PolicyTest.php`.
