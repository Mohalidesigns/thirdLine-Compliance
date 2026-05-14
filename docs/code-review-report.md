# Atheris Compliance — MVP Final Code Review

**Date:** 2026-05-14
**Reviewer:** Code-Review Agent (Claude Opus 4.7)
**Scope:** Phase-0 foundation + Phase-1 modules (Library, Sanctkb, Calendar, Dashboard, Audit) + QA pass
**Inputs:** `CLAUDE.md`, `STATUS.md`, `docs/design-spec.md`, `Atheris_Multi_Agent_Build_Prompt.md` §0.4 DoD

---

## Summary verdict

**APPROVE-WITH-CHANGES** — for local-only MVP demo.

The platform compiles cleanly (`npm run build` 1.57s; 338 kB / 111 kB gzip) and **78/78 Pest tests pass**. The audit hash-chain trigger is well-designed, the SHA-256 prev_hash linkage is correct, append-only enforcement via UPDATE/DELETE triggers works, and the `EmitsAuditEvent` `wasRecentlyCreated` bug (D-001) is genuinely fixed. However, **three substantial defects must be resolved before the demo is usable end-to-end**: missing CRUD pages, the absent root `.gitignore`, and the systemic double-audit-write pattern. None of these break the test suite — they break user-visible flows or production-readiness.

---

## Verdict reasoning

What the team got right:
- Audit chain is the right shape: synchronous, throws-on-failure, Postgres triggers reject UPDATE/DELETE, hash chain verified in real integration tests against live Postgres.
- The `EmitsAuditEvent` fix to use separate `created`/`updated` hooks is correct for every call path I traced (`Model::create`, `$model->save`, `$model->update`, `updateOrCreate`, `firstOrCreate`). The known Eloquent gaps (`Builder::update()` mass-update, `upsert()`, `Builder::delete()`) are not exercised anywhere in the codebase — verified by grep.
- Input validation via FormRequests with `exists` rules on all FK columns.
- Mass assignment: every model has explicit `$fillable`.
- No `dangerouslySetInnerHTML`, no tokens in `localStorage` (only sidebar UI state).
- Tailwind v4 CSS-first design tokens correctly implemented; AuthenticatedLayout matches spec, mobile breakpoint handled.
- Tests have meaningful assertions, no `markTestSkipped`, no `assertTrue(true)`.
- Postgres integration tests use a dedicated `pgsql_integration` connection so they exercise real triggers.

What blocks final sign-off:
- Three Inertia pages are missing (`Create.tsx`, `Edit.tsx`, `Show.tsx` for Instruments / Obligations / Sanctions). Controllers route to them but they don't exist; clicking "Add Instrument" or any edit link will 500.
- No `.gitignore` at repo root — `.env` (with `APP_KEY` base64) is currently untracked only because the repo has zero commits.
- Double-audit-write pattern: every controller's `auditWriter->record(...)` AND the `EmitsAuditEvent` trait both fire, producing 2 rows per state change. QA flagged this in §Outstanding Gaps but it was not fixed.

---

## Defects found

### Blocker — must fix before demo

**B-01. Missing Create / Edit / Show Inertia pages for Instruments, Obligations, Sanctions.**
- Files referenced but missing:
  - `resources/js/Pages/Instruments/Create.tsx`, `Edit.tsx`, `Show.tsx`
  - `resources/js/Pages/Obligations/Create.tsx`, `Edit.tsx`, `Show.tsx`
  - `resources/js/Pages/Sanctions/Create.tsx`, `Edit.tsx`, `Show.tsx`
- Evidence: `InstrumentsController::create/edit/show` calls `Inertia::render('Instruments/Create', …)` etc.; `resources/js/Pages/Instruments/` contains only `Index.tsx`.
- Impact: every "Add", "Edit", and detail-link click on the workbench returns a 500 (Inertia page-not-found). The CRUD flow advertised in STATUS.md is non-functional end-to-end despite the routes existing.
- Owner: frontend-engineer.
- Fix: build the nine missing pages per design-spec §C.2 (form controls) and route names already wired.

**B-02. Repository has no root `.gitignore`; `.env` is one `git add .` away from being committed.**
- File: missing `/Users/mac/Documents/devs/atheris-compliance/.gitignore`
- Evidence: `find` returns only `database/.gitignore`, `bootstrap/cache/.gitignore`, `storage/*/.gitignore`. No root file. `.env` contains a live `APP_KEY=base64:QHnn58xni+TvnCci5NT9Nmnt9I8aWqVnRlu+ot5dJ0w=`. `git -C ... ls-files` returns zero (no commits).
- Impact: the first `git add .` commits `.env`, `vendor/`, `node_modules/`, `public/build/`, `.phpunit.result.cache`, `storage/logs/*`. Application key leak on push.
- Owner: foundation-agent / devops.
- Fix: drop in the canonical Laravel 13 `.gitignore` template (`/vendor`, `/node_modules`, `/public/build`, `/public/hot`, `/storage/*.key`, `.env`, `.env.backup`, `.env.production`, `.phpunit.result.cache`, `Homestead.json`, `Homestead.yaml`, `auth.json`, `npm-debug.log`, `yarn-error.log`, `/.fleet`, `/.idea`, `/.vscode`).

**B-03. Missing `POST /instruments/bulk-import` route + handler.**
- Files: `routes/web.php` — no entry; `modules/Library/app/Http/Controllers/InstrumentsController.php` — no `bulkImport` method; `resources/js/Pages/Instruments/Index.tsx:206-209` — the SecondaryButton has no `onClick` / no `useForm` wiring.
- Evidence: `BulkImportInstrumentsJob` and `InstrumentImport` exist, are tested, and work — but no UI/route surface connects them.
- Impact: Day-1 import button is non-functional. Already flagged by QA (§Outstanding Gaps #5).
- Owner: backend-engineer + frontend-engineer.
- Fix: add `Route::post('/instruments/bulk-import', [InstrumentsController::class, 'bulkImport'])` (auth+verified), build the controller method (validate XLSX upload, `dispatch(new BulkImportInstrumentsJob($request->file('file')->store('imports')))`, flash success), wire the button to `router.post(...)` with a hidden file input.

---

### High — fix before production; acceptable to demo with the trade-off documented

**H-01. Double-audit-write pattern produces 2 rows per state change for every create/update/delete in every module.**
- Files affected (every state-changing controller action):
  - `modules/Library/app/Http/Controllers/InstrumentsController.php:74-78, 104-109, 119-122`
  - `modules/Library/app/Http/Controllers/ObligationsController.php:100-104, 130-135, 145-147`
  - `modules/Sanctkb/app/Http/Controllers/SanctionsController.php:89-93, 121-126, 138-140`
  - `modules/Library/app/Imports/InstrumentImport.php:67-70` (also fires `instrument.imported` in addition to the trait's `instrument.created`)
- Evidence: `EmitsAuditEvent` trait fires `created`/`updated`/`deleted` automatically on the Eloquent model events; the controllers then ALSO call `auditWriter->record(...)` with the same action.
- Impact: doubled audit volume; the hash chain still computes correctly but every business operation produces redundant rows. The duplicate rows differ only in the `context` payload (controller adds `created_by`/`updated_by`/`changes` vs trait's `changes`). For a compliance system this is wrong — auditors will see two `instrument.created` events with the same `subject_id` at near-identical timestamps and question integrity.
- Orchestrator policy (per task brief): trait fires for Eloquent state changes; controllers call `AuditWriter::record(...)` ONLY for non-Eloquent events (e.g., `instrument.bulk_import_started`, `instrument.exported_csv`). The codebase does not match this rule.
- Owner: backend-engineer.
- Fix: remove the explicit `auditWriter->record(...)` calls in `store/update/destroy` actions on all three controllers; keep them only where the trait cannot capture the event (e.g., the `instrument.imported` row in `InstrumentImport` should also be removed once the trait's `instrument.created` covers it — the difference is the `source_row_hash` context, which can be enriched via a custom event or by overriding `auditActionPrefix` for import context).

**H-02. Cross-module Eloquent imports violate ADR-017 / multi-agent prompt §0.3 K4.**
- Files:
  - `modules/Dashboard/app/Http/Controllers/DashboardController.php:11-14` — imports `Modules\Library\Models\Instrument`, `Modules\Library\Models\Obligation`, `Modules\Sanctkb\Models\Sanction`. Calls `::count()`, `::whereNotNull(...)`, `::with(...)` directly.
  - `modules/Calendar/app/Http/Controllers/CalendarController.php:13` — imports `Modules\Sanctkb\Models\Sanction`, queries it directly at line 29-33.
  - `modules/Sanctkb/app/Http/Controllers/SanctionsController.php:14` — imports `Modules\Library\Models\Regulator`. Acceptable when the cross-module model is the "reference taxonomy" (Regulator is a shared lookup), but the rule per K4 is "service classes only".
  - `modules/Sanctkb/app/Models/Sanction.php:14` — `BelongsTo(Regulator::class)` is a cross-module Eloquent relationship.
- Evidence: the K4 constraint says "modular monolith with strict boundaries; no cross-module Eloquent relationships". STATUS.md does not document this exception.
- Impact: when the Dashboard module is extracted into a separate service in Phase 2, every direct model access becomes a refactor target. For MVP local, this works.
- Owner: backend-engineer.
- Fix: route every cross-module read through a service. The `InstrumentService` already exposes `countByRegulator()` and `obligationsForCalendar()`; add `SanctionService::count()`, `SanctionService::upcomingByDateRange($from, $to)`. Then Dashboard and Calendar consume services only. Sanction → Regulator relationship is harder; the pragmatic option is to formally document Regulator as a shared "reference data" model owned by Library and accept the FK from Sanctkb to it (or move Regulator to a shared `Modules\Reference\` later).

**H-03. The XLSX import path writes an `instrument.imported` audit row that duplicates the trait's `instrument.created` for every imported row.**
- File: `modules/Library/app/Imports/InstrumentImport.php:67-70`
- Evidence: `Instrument::create(...)` fires the trait's `instrument.created` event; the import then explicitly writes `instrument.imported` for the same subject. Result: 2 audit rows per imported row, action strings differ. The orchestrator's rule (per brief) is that controllers/jobs may emit non-Eloquent action names for events that have no model-save counterpart — `instrument.imported` is debatable here because the row IS saved, so the trait covers it.
- Owner: backend-engineer.
- Fix: decide on one. Recommended: drop the explicit `instrument.imported` row (the trait's `instrument.created` already carries the source row in context if you extend the trait) OR change the trait to suppress its own write when an import is in progress (cleaner: emit a single `instrument.bulk_import_completed` row at job end with the count + hash list).

**H-04. `audit_events.context` is hashed via `JSONB::TEXT`, which is Postgres-canonical, not byte-stable across PG versions or off-engine verifiers.**
- File: `database/migrations/2026_05_14_190000_create_audit_events_table.php:98`
- Evidence: `convert_to(NEW.context::TEXT, 'UTF8')` — when JSONB is cast to TEXT, Postgres re-serialises (key order may change, whitespace normalised). The hash is stable for any verifier that runs the same Postgres version, but an external auditor verifying the chain from raw exported JSON would compute a different SHA-256.
- Impact: for MVP single-tenant single-instance, fine. For BRD K8 "daily Merkle root signed by HSM and sealed to S3 Object Lock" and external attestation, this means the canonical form must be the Postgres rendering — every external verifier needs Postgres to recompute.
- Acceptable as a documented limitation for MVP; flag for the audit/integrity work in Phase 2.
- Owner: backend-engineer / DevOps.
- Fix (later): switch to `jsonb_strip_nulls(NEW.context)::TEXT` + a documented canonicalisation function, OR store the canonical bytes alongside the row (`canonical_bytes BYTEA NOT NULL`) so external verifiers don't have to round-trip through Postgres.

**H-05. `Audit` middleware is registered but never applied to any route.**
- Files: `bootstrap/app.php:22` registers the `'audit'` alias; no route in `routes/web.php` or in any module's routes uses `->middleware('audit')`.
- Evidence: grep for `middleware('audit')` and `middleware([…'audit'`] returns only the `AuditServiceProvider`'s `protected string $nameLower = 'audit';` (unrelated).
- Impact: the HTTP-level audit row (`'http.request'` with method/status/IP) is never written. Only Eloquent-driven model events get audited. For BRD/TRD compliance, every state-changing HTTP request should be audited even when no model is touched (e.g., a failed validation, a 4xx, a search/export that returns no rows but should still be auditable).
- Acceptable for MVP local demo (model audit covers most), but documented for production.
- Owner: backend-engineer / foundation-agent.
- Fix: append `'audit'` to the `web` middleware group in `bootstrap/app.php` OR apply per-group on the auth-protected route block in `routes/web.php`.

---

### Medium — fix when time permits

**M-01. Three Pages/*/Index.tsx files default to hardcoded mock data when Inertia props are absent.**
- Files: `resources/js/Pages/Instruments/Index.tsx:55-77`, `resources/js/Pages/Obligations/Index.tsx`, `resources/js/Pages/Sanctions/Index.tsx`
- Evidence: each file declares `mockPaginated`, `mockData`, and assigns the default via `function InstrumentsIndex({ instruments = mockPaginated, … })`.
- Impact: if the backend ever fails to pass `instruments` (a regression), the UI silently displays the same 8 mock instruments to every user, which is a security/auditing hazard (users may believe the system is showing real data). Defensive defaults should be empty arrays, not mock business data.
- Owner: frontend-engineer.
- Fix: change defaults to `instruments = { data: [], links: [], from: 0, to: 0, total: 0 }`; delete the mock fixtures.

**M-02. Instruments `actions` IconButtons (View/Edit/Delete) have no onClick handlers.**
- File: `resources/js/Pages/Instruments/Index.tsx:181-191`
- Impact: the three icons render but do nothing. View should `Link` to `route('instruments.show', row.id)`, Edit to `route('instruments.edit', row.id)`, Delete should POST via `router.delete` with a confirmation.
- Owner: frontend-engineer.

**M-03. `HashChainTest` insertion-count test is fragile to pre-existing rows.**
- File: `tests/Integration/Audit/HashChainTest.php:91`
- Already flagged in STATUS.md D-006; the truncate workaround is in place but the long-term fix (filter by batch/action prefix) is deferred.
- Owner: qa-engineer (already acknowledged).

**M-04. `\DB::getDriverName()` uses leading-backslash global resolution despite the file importing `Illuminate\Support\Facades\DB`.**
- File: `modules/Library/app/Services/InstrumentService.php:37, 50, 62`
- Impact: code smell, minor inconsistency with the file's imports. Functionally harmless.
- Owner: backend-engineer. Fix via Pint.

**M-05. `RateLimiter` / route-level rate limiting not applied to login, register, password-reset.**
- Files: `routes/auth.php` (Breeze default).
- Evidence: Breeze's defaults usually include `throttle:6,1` on login; verify Breeze 2.4.1 still applies the throttle. (Not re-verified in this review.)
- Owner: backend-engineer / foundation-agent.

**M-06. `Sanctkb/SanctionsController::index` accepts `?search=` and pipes it into `Sanction::fullText($search)`, which uses `plainto_tsquery('english', ?)` — parameter-bound, safe. The category filter uses `whereHas('regulator', fn $q => $q->where('code', $filters['category']))` — parameter-bound, safe. No injection vector found; this is a "what was good" item, not a defect. Noted here to document the verification.

**M-07. `bulk-import` is missing (B-03) but `InstrumentImport.php` uses `auth()->id()` in `imported_by` context (line 69) — when run from a queued job, `auth()` returns null. Document or fix.**

---

### Low / Nit (optional)

**L-01.** Module README files are template placeholders ("Owned Tables: TBD", "Public Service Surface: TBD"). Each is ~30 lines but the *content* is mostly TODOs. Worth a 10-minute pass to fill in actual table names and service signatures.

**L-02.** `EmitsAuditEvent::auditActionPrefix()` uses `str(class_basename(static::class))->snake()` — fine, but every model overrides it anyway (Instrument → `'instrument'`, Obligation → `'obligation'`, Sanction → `'sanction'`). The override is redundant since the default would produce the same string. Cleaner to drop the overrides.

**L-03.** `CalendarController::ics` builds the ICS body via string concatenation. The escape via `str_replace(["\n", "\r"], ' ', ...)` is brittle; consider an ICS-aware library or at minimum strip semicolons/colons per RFC 5545 if a user-controlled obligation title could legitimately contain them.

---

## What was good

1. **Audit-chain design is excellent for MVP.** Synchronous, throws-on-failure, append-only via Postgres triggers, hash chain verified in real integration tests (`tests/Integration/Audit/HashChainTest.php`) against a live Postgres 17 instance with `pgcrypto`. The hash-chain test computes the expected hash via the same `digest()` Postgres function used in the trigger, so it really verifies the trigger logic.
2. **EmitsAuditEvent fix is correct.** Splitting `saved` into `created`+`updated` properly resolves the `wasRecentlyCreated` issue. The known Eloquent gaps (mass-update via `Builder::update()`, `upsert()`, `Builder::delete()`) are NOT exercised in the codebase — verified by grep. For MVP this is safe.
3. **Tests are real.** 78 Pest tests with 536 assertions; the integration suite exercises end-to-end Inertia + HTTP + DB flows, the audit-trail tests verify subject_type / actor_id / tenant_id correctly, and the immutability test confirms Postgres rejects UPDATE/DELETE on `audit_events`.
4. **Frontend hygiene.** No `dangerouslySetInnerHTML`, no `any` types found in spot-checks, components match the design spec (sampled DataTable, StatusBadge, AuthenticatedLayout, PageHeader), ARIA attributes present (`aria-busy`, `aria-sort`, `aria-label`, `role="status"`, `role="region"` on FlashNotification, `aria-live="polite"`).
5. **Mass-assignment, input validation, parameterised queries.** Every model has `$fillable`. FormRequests with `exists:` rules on every FK. All `DB::statement` calls are static DDL (no user input). The only `whereRaw` is `Sanction::scopeFullText` which parameter-binds.
6. **The Postgres triggers DO refuse UPDATE/DELETE.** Even when called via `DB::table('audit_events')->update(...)` (verified by `AuditTrailEndToEndTest::it update and delete on audit_events are rejected on postgresql`).

---

## Verification commands run

```
$ composer test
INFO  Configuration cache cleared successfully.
{"tool":"pest","result":"passed","tests":78,"passed":78,"assertions":536,"duration_ms":3547}

$ npm --prefix /Users/mac/Documents/devs/atheris-compliance run build
public/build/assets/app-DALxAIu5.js  338.01 kB │ gzip: 111.06 kB
✓ built in 1.57s
```

Build clean, all tests green.

Greps for security signals:
- `grep -rn "dangerouslySetInnerHTML" resources/` → no matches.
- `grep -rn "Log::" modules/ app/` → only `app/Http/Middleware/Audit.php:71` (no PII; logs only exception message + route name on non-2xx).
- `grep -rn "DB::statement|DB::raw|DB::select" modules/ app/` → all hits are static DDL or fixed aggregation queries with no user input.
- `find . -maxdepth 2 -name ".gitignore"` → no root `.gitignore`.
- `git -C . ls-files | wc -l` → 0 (no commits yet).

---

## Areas that were thoroughly reviewed

- `app/Concerns/EmitsAuditEvent.php` — full read, traced every Eloquent event hook path
- `app/Services/AuditWriter.php` — full read
- `app/Concerns/BelongsToTenant.php` — full read
- `app/Casts/Encryptable.php` — full read
- `app/Http/Middleware/Audit.php` — full read; flagged not-wired-up (H-05)
- `bootstrap/app.php` — full read
- `database/migrations/2026_05_14_190000_create_audit_events_table.php` — full read, traced trigger logic
- `routes/web.php` — full read
- `modules/Library/app/Http/Controllers/InstrumentsController.php` — full read
- `modules/Library/app/Http/Controllers/ObligationsController.php` — full read
- `modules/Library/app/Models/Instrument.php` — full read
- `modules/Library/app/Models/Obligation.php` — full read
- `modules/Library/app/Services/InstrumentService.php` — full read
- `modules/Library/app/Imports/InstrumentImport.php` — full read
- `modules/Library/app/Jobs/BulkImportInstrumentsJob.php` — full read
- `modules/Sanctkb/app/Http/Controllers/SanctionsController.php` — full read
- `modules/Sanctkb/app/Models/Sanction.php` — full read
- `modules/Sanctkb/database/migrations/2026_05_15_000004_create_sanctions_table.php` — full read
- `modules/Calendar/app/Http/Controllers/CalendarController.php` — full read
- `modules/Dashboard/app/Http/Controllers/DashboardController.php` — full read
- `tests/Integration/Audit/HashChainTest.php` — full read
- `tests/Feature/Integration/AuditTrailEndToEndTest.php` — full read
- `tests/Feature/Integration/CrossModuleIntegrationTest.php` — partial (first 100 lines)
- `tests/Feature/Integration/InstrumentImportTest.php` — partial (first 100 lines)
- `resources/js/Layouts/AuthenticatedLayout.tsx` — full read
- `resources/js/Components/DataTable.tsx` — full read
- `resources/js/Components/StatusBadge.tsx` — full read
- `resources/js/Pages/Instruments/Index.tsx` — full read

---

## Areas that were spot-checked

- Module READMEs — opened Library README only, scanned line counts for the rest
- Form Requests — opened `StoreInstrumentRequest` only
- 27 frontend components — checked file list, read DataTable + StatusBadge + AuthenticatedLayout in full; assumed the design-spec was followed for the other 24 based on the sample
- `BelongsToTenant` global scope behaviour — read code, did not exercise edge cases under concurrent multi-tenant queries (not in MVP scope anyway)
- `Encryptable` cast — read but not stress-tested for the KMS-swap scenario

---

## Areas that were NOT reviewed

- **Authenticated-page accessibility** (`/dashboard`, `/instruments`, `/sanctions`, `/calendar`) — Pa11y was not re-run on authenticated routes (deferred to QA Playwright work).
- **Visual rendering** — no Playwright/Dusk; I read TSX source but did not render in a browser. Sidebar collapse behaviour, mobile breakpoint, focus rings, keyboard tab order — code looks right but unverified visually.
- **Performance budgets** — no Lighthouse, no k6, no load testing. Bundle size (338 kB / 111 kB gzip) is fine but unbudgeted.
- **Postgres role permissions** — confirmed in STATUS.md that the app uses the `postgres` superuser locally, which would bypass triggers if exploited. Flagged as known MVP limitation per task brief.
- **Per-module ServiceProvider boot order** — modules are autoloaded by nwidart/laravel-modules; I did not trace whether `module.json` registration order matters for the trait boot hooks. The 78-test pass implies it works.
- **Spatie permission tables / RBAC** — installed but not enforced (deferred per spec).
- **PHPStan / Larastan** — not configured; not reviewed.
- **`composer audit` / dependency CVE scan** — not run.
- **The 17 non-Index frontend components** (Card, Dropdown, FilterBar, EmptyState, FlashNotification, Modal, ConfirmDialog, etc.) — file list confirmed all 27 present; spec compliance assumed but not line-checked.
- **Cross-tenant data leakage** — single-tenant MVP, scope traits in place, but the multi-tenant isolation invariants (e.g., `BelongsToTenant` global scope being bypassable via `withoutGlobalScopes`) were not stress-tested.
- **The `Obligations` and `Sanctions` filter logic** — read the index methods only, did not exercise every filter combination.

---

## Definition-of-Done MVP scorecard

Mapped to `Atheris_Multi_Agent_Build_Prompt.md` §0.4. "Deferred-accepted" means the task brief explicitly removed the item for MVP local-only.

| # | DoD criterion | Status | Notes |
|---|---|---|---|
| 1 | Functional behaviour matches BRD `FR<m>.<n>` | PARTIAL | Library/Sanctkb/Calendar/Dashboard index pages work; Create/Edit/Show flows broken (B-01) |
| 2 | Pest coverage ≥80% on changed lines; mutation ≥70% | NOT MEASURED | Pest coverage plugin not configured; 78 tests / 536 assertions across the changed surface is healthy but unquantified |
| 3 | PHPStan/Larastan level 9, Psalm, Pint, ESLint | DEFERRED-ACCEPTED | None set up; Pint defaults available via `vendor/bin/pint` but not enforced |
| 4 | Snyk / OWASP DC / Trivy / Gitleaks all green | DEFERRED-ACCEPTED | Not configured. Manual review found no obvious vulnerabilities |
| 5 | Documentation (README, ADR, OpenAPI) | PARTIAL | STATUS.md is excellent; module READMEs are TBD-heavy stubs; no ADRs added |
| 6 | Audit-trail emission on every state change | MET (with caveat H-01) | Verified by integration tests; double-write pattern means each change produces 2 rows |
| 7 | Maker-checker server-side | DEFERRED-ACCEPTED | Not in MVP scope |
| 8 | No PII in logs / non-prod | MET | Grepped `Log::` → only `Audit` middleware log of `route` + `exception.message`. No PII handling. |
| 9 | Octane-safe | DEFERRED-ACCEPTED | No Octane in MVP |
| 10 | CI green | DEFERRED-ACCEPTED | No CI; `composer test` + `npm run build` pass locally |

---

## Risks for the user to be aware of as they move toward demo / production

1. **Demo flow risk:** the Index pages of all five modules work — `/dashboard`, `/instruments`, `/obligations`, `/sanctions`, `/calendar` all return 200 with real data per the QA smoke. But every "Add", "Edit", and detail-link click will 500. Plan the demo around list views only, OR fix B-01 first.
2. **Git hygiene risk:** the very first `git add . && git commit` after this review will commit `.env` (with `APP_KEY`), `vendor/`, `node_modules/`, and build artefacts. Stop and create `.gitignore` before the first commit. (Per project policy I did not create the file — flagging only.)
3. **Audit volume risk:** the double-write pattern is currently doubling all audit row counts. The integrity is intact (chain still verifies), but row counts in reports will be 2× expected. Decide on the audit-write contract (trait-only vs trait+controller) before the first auditor review.
4. **Cross-module Eloquent debt:** when modules are extracted into separate deployable services in Phase 2, the Dashboard and Calendar controllers' direct model imports become refactor work. Capture this as a known Phase-2 cost.
5. **Audit chain canonicalisation:** the SHA-256 chain uses Postgres-canonical JSONB::TEXT. External auditors verifying the chain from raw rows must use Postgres to recompute. This is acceptable for MVP but should be re-specified before any third-party attestation contract.
6. **Postgres role privilege:** the Laravel app currently connects as `postgres` superuser, which can bypass the BEFORE UPDATE / BEFORE DELETE triggers via `ALTER TABLE ... DISABLE TRIGGER`. For production, create a dedicated `atheris_app` role with only INSERT/SELECT on `audit_events` and explicitly no superuser. Documented in task brief as known limitation.
7. **No CSP / security headers** — Inertia + Breeze ship sensible defaults but no explicit Content-Security-Policy, X-Frame-Options, Referrer-Policy. For demo on localhost this is fine; for any external exposure (even staging), add a middleware.
8. **No rate limiting verified** on `/login` — Breeze 2.4.1 likely still applies `throttle:6,1` by default, but I did not re-verify. Worth a 30-second check before exposing the URL.

---

## Handoff

| Defect | Owner |
|---|---|
| B-01 (missing Create/Edit/Show pages) | frontend-engineer |
| B-02 (no root `.gitignore`) | foundation-agent / devops |
| B-03 (no bulk-import route) | backend-engineer + frontend-engineer |
| H-01 (double-audit-write) | backend-engineer |
| H-02 (cross-module Eloquent) | backend-engineer |
| H-03 (import duplicate audit row) | backend-engineer |
| H-04 (canonicalisation policy) | backend-engineer / DevOps — Phase 2 |
| H-05 (Audit middleware not applied) | backend-engineer / foundation-agent |
| M-01 / M-02 (mock-data fallback, missing action handlers) | frontend-engineer |
| M-03 (HashChainTest robustness) | qa-engineer — already acknowledged |
| M-04 (`\DB::` leading backslash) | backend-engineer — `vendor/bin/pint` |
| M-05 (auth throttling verify) | backend-engineer |
| L-01 / L-02 / L-03 (nits) | optional |

### What would change the verdict from APPROVE-WITH-CHANGES to APPROVE-FOR-MVP

Resolve B-01, B-02, B-03 and pick one direction for H-01 (either remove the explicit `auditWriter->record(...)` from controllers, or document that double-rows are intentional and tests assert exactly 2 rows per change). The other Highs are acceptable as documented known issues for a local-only MVP demo.

