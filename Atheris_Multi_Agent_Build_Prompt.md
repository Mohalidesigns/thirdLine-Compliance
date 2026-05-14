# ATHERIS — MULTI-AGENT BUILD ORCHESTRATION PROMPT
### Brief to six co-working agents executing the Atheris Compliance Management Solution end-to-end

| Field | Value |
|---|---|
| Project | Atheris Compliance Management Solution |
| Build target | Production go-live of all 30 modules (M01–M30) per BRD v2.0 |
| Date | 14 May 2026 |
| Owner | Mohammed Ali |
| Document role | Single master orchestration brief for all six agents — every agent reads §0–§3 then their own section in §4 |

---

## 0. Pre-flight — Every Agent Reads This First

You are one of six agents collaborating to build **Atheris**, an integrated regulatory compliance management platform for Nigerian and African financial institutions. Your work is part of a single coordinated build. **Do not begin implementation until you have read §0–§3 of this document and your role section in §4.**

### 0.1 Sources of Truth — Order of Precedence

When the documents disagree, the order below resolves the conflict. **Never silently deviate.** If you believe a document is wrong, flag it as a `[NEEDS DECISION]` in your status update; do not invent an unsanctioned alternative.

| # | Document | Role |
|---|---|---|
| 1 | **`Atheris_Compliance_BRD_v2.0.md`** | Functional source of truth — what to build, for whom, against which obligation, with which acceptance criteria. 30 modules (M01–M30), all functional rules (`FR<m>.<n>`), all acceptance criteria (`AC<m>.<n>`), all NFRs (`NFR<n>`). |
| 2 | **`Atheris_Compliance_TRD_v1.1_Laravel.md`** | Technical source of truth — how to build, with which stack, against which constraints. Architecture, bounded contexts, data model, APIs, security, NFR implementation, DR, observability, DevOps, migration. |
| 3 | **`AuditPro_GRC_Design_System.md`** | UI source of truth — colors, typography, spacing, component library, layouts, accessibility. Drives the Inertia/React workbench and the Tailwind tokens in `resources/css/app.css`. |
| 4 | **`Compliance Management Toolkits DRAFT.xlsm`** (in `/uploads`) | Day-1 data source — 43 regulators, 13 instrument types, 29 areas of focus, 352 instruments, 1,227+ CRMP rows, 236 monitoring activities, 187 returns, 417 sanctions. |

### 0.2 Mission Statement (One Sentence)

> Ship a production-grade, Nigeria-resident, audit-ready Laravel platform that operationalises every CBN, NDIC, NFIU, SEC, NDPC, FIRS, NAICOM, PenCom and adjacent compliance obligation captured in the canonical Drafts, with measurable SLOs, immutable audit trail and a Day-1 load of 352 obligations + 12 CRMP themes + 187 returns + 417 sanctions.

### 0.3 Immovable Constraints

These are decisions already made. Do not re-litigate them.

| ID | Constraint | Source |
|---|---|---|
| K1 | **Stack:** PHP 8.3 + Laravel 11 LTS + Octane (RoadRunner). Frontend = Inertia.js + React + TypeScript + Tailwind for workbench; Filament 3 for admin CRUD | TRD §6.1, ADR-001a, ADR-008a |
| K2 | **Database:** PostgreSQL 16 (Aurora); no exceptions. Search = OpenSearch 2.x. Cache = Redis 7. Stream = Kafka 3.x (MSK) | TRD §6.1, ADR-003 |
| K3 | **Region:** Nigeria-resident production data; CloudHSM-in-NG keys; S3 with Object Lock (Compliance mode) | TRD §5, NFR41–43 |
| K4 | **Modular monolith** with strict boundaries via `nwidart/laravel-modules`; no cross-module Eloquent relationships | TRD ADR-017 |
| K5 | **Workflow engine:** Temporal (PHP SDK) for long-running flows; `spatie/laravel-model-states` for short state machines | TRD ADR-009a |
| K6 | **AML rule engine:** Python 3.12 + FastAPI sidecar consuming Kafka `txn.ingested`; the Laravel `aml` module does NOT evaluate rules | TRD ADR-010a |
| K7 | **Identity:** Keycloak federated to bank IdP via Socialite (OIDC); MFA at the IdP; Sanctum cookies for SPA; Passport for partner OAuth 2.1 | TRD §13.2 |
| K8 | **Audit trail:** hash-chained `audit.event` table + daily Merkle root signed by HSM and sealed to S3 Object Lock. Append-only DB role; no UPDATE/DELETE possible | TRD §10.19, ADR-011 |
| K9 | **Field-level PII encryption:** `Encryptable` Eloquent cast wrapping AWS KMS DEKs around CloudHSM CMKs; `pgcrypto` for lookup-needing fields | TRD ADR-018 |
| K10 | **Performance budgets:** screening p95 ≤500ms; onboarding p95 ≤3s; AML alert p99 ≤30s; search p95 ≤2s; dashboard refresh ≤5min | BRD §9.1; TRD §14.1 |
| K11 | **OWASP ASVS Level 2** minimum across the codebase; ISO 27001-aligned ISMS for the platform itself | TRD §13.6 |
| K12 | **Day-1 load** must complete using `maatwebsite/excel` + Horizon jobs with idempotent `source_row_hash` deduplication | TRD §19, ADR-014a |

### 0.4 Definition of Done — Cross-Cutting

A unit of work — module, page, connector, workflow, infrastructure component — is **only** done when:

1. **Functional behaviour** matches the BRD `FR<m>.<n>` rules and **passes every `AC<m>.<n>` acceptance criterion** that applies.
2. **Tests:** Pest unit + feature + integration coverage ≥ 80% on changed lines; mutation score ≥ 70% (≥85% for financial-crime modules).
3. **Static analysis:** PHPStan/Larastan at level 9 passes; Psalm passes; Pint and ESLint pass.
4. **Security:** Snyk + OWASP Dependency-Check + Trivy + Gitleaks all green; no High/Critical findings.
5. **Documentation:** changelog entry, OpenAPI updated if API touched, README updated if module structure touched, ADR added if architecture decision recorded.
6. **Audit-trail emission:** every state-changing path writes to `audit.event` via the `EmitsAuditEvent` trait or explicit `AuditWriter` call before returning success.
7. **Maker-checker** enforced server-side for every state-changing operation on which the BRD requires it.
8. **No PII in logs, no PII in non-prod environments** without pseudonymisation; no tokens in `localStorage`; no `eval`, `unserialize` on untrusted input.
9. **Octane-safe:** the `octane:check` linter passes; no static caches holding request state; container bindings are `scoped()` where request-bound.
10. **CI green** end-to-end including k6 smoke; ArgoCD has reconciled `dev` and `staging`.

### 0.5 Workspace and Document Locations

All canonical inputs sit alongside this prompt in the project folder:

```
Compliance Solution/
  Atheris_Compliance_BRD_v2.0.md            ← functional spec
  Atheris_Compliance_TRD_v1.1_Laravel.md    ← technical spec (Laravel variant)
  Atheris_Multi_Agent_Build_Prompt.md       ← this file
  AuditPro_GRC_Design_System.md             ← UI source of truth
  uploads/Compliance Management Toolkits DRAFT.xlsm  ← Day-1 data
```

The codebase will be built at `<repo>/atheris-platform/`.

---

## 1. The Team — Six Agents

| # | Agent | Charter (one line) |
|---|---|---|
| A1 | **Foundation Agent** | Lays the Laravel modular-monolith skeleton, base packages, audit chain, Octane runtime, CI/CD scaffold |
| A2 | **Backend Domain Agent** | Implements every bounded context (M01–M30) — Eloquent models, services, jobs, events, Kafka producers/consumers, Temporal workflows |
| A3 | **UI / Frontend Agent** | Builds the Inertia + React workbench from the AuditPro Design System — components, layouts, workbench pages, dashboards |
| A4 | **Filament Admin Agent** | Builds Filament 3 resources for every CRUD-heavy module (M01–M05, M10, M14, M15, M17, M22, M24–M30, M28) — tables, forms, actions, widgets, bulk imports |
| A5 | **Integrations Agent** | Builds every external connector (NIBSS, NIMC, CAC, FIRS, NIS, FRSC, INEC, NFIU goAML, eFASS, NDIC, SEC, PenCom, NDPC, SWIFT, FATCA, CRS) + Python AML sidecar + Temporal worker + optional Go screening service |
| A6 | **DevOps / SRE / Migration / QA Agent** | Builds Terraform/Helm/EKS deployment to AWS af-south-1 active-active, observability stack, CI/CD pipeline, Day-1 data loader, DR runbooks, performance tests |

### 1.1 Dependency Graph

```
                    ┌─────────────────────┐
                    │  A1 Foundation      │  (must-finish-first scaffold; then runs concurrently)
                    └──────────┬──────────┘
                               │
                ┌──────────────┼──────────────────────────────┐
                │              │                              │
                ▼              ▼                              ▼
       ┌─────────────┐  ┌──────────────┐              ┌──────────────┐
       │ A2 Backend  │◄─┤ A3 UI/Front  │              │ A6 DevOps    │
       │ Domain      │  │ (design sys) │              │ /Infra       │
       └──────┬──────┘  └──────┬───────┘              └──────┬───────┘
              │                │                              │
              ▼                ▼                              │
       ┌─────────────┐  ┌──────────────┐                      │
       │ A4 Filament │  │ A5 Integr-   │                      │
       │ Admin       │  │ ations       │◄─────────────────────┘
       └─────────────┘  └──────────────┘
              │                │
              └────────┬───────┘
                       ▼
              ┌──────────────────┐
              │  A6 QA / Day-1   │  (returns at the end for Day-1 load + perf + DR drills)
              │  Migration       │
              └──────────────────┘
```

**Read this graph as:**
- A1 must produce the **scaffold** (modules layout, base traits, Octane config, base CI) before A2/A3/A4/A5 can start in earnest. After that, A1 continues to deepen cross-cutting concerns (audit ledger, encryption, Octane discipline) in parallel.
- A2 and A3 work in parallel; A4 consumes A2's Eloquent models; A5 produces clients A2 calls.
- A6 starts on Day 0 building the cloud landing zone, then runs continuously, and owns the Day-1 data load + performance/DR drills at the back end.

---

## 2. Repository Layout

Every agent works in the same monorepo. Branch protection requires PR review by the module owner + a second reviewer.

```
atheris-platform/
├── app/                          # Laravel framework code (cross-cutting)
│   ├── Casts/Encryptable.php
│   ├── Concerns/BelongsToTenant.php
│   ├── Concerns/EmitsAuditEvent.php
│   ├── Console/Commands/*.php    # Scheduled commands
│   ├── Http/Middleware/*.php     # OctaneScope, Audit, IdempotencyKey, EncryptionAccess
│   ├── Providers/*.php
│   └── Services/AuditWriter.php
├── modules/                      # nwidart/laravel-modules — one per BRD bounded context
│   ├── Library/                  # M01, M03
│   ├── Policy/                   # M02
│   ├── Crmp/                     # M04
│   ├── Controls/                 # M05
│   ├── Returns/                  # M10, M17
│   ├── Screening/                # M07
│   ├── Aml/                      # M08 (proxy to Python sidecar)
│   ├── Nfiu/                     # M09
│   ├── Customer/                 # M06
│   ├── Complaints/               # M11
│   ├── Whistle/                  # M13
│   ├── Conduct/                  # M22
│   ├── Abac/                     # M24
│   ├── Gov/                      # M25
│   ├── Dpo/                      # M12, M16
│   ├── Tax/                      # M21
│   ├── Fatca/                    # M20
│   ├── Esg/                      # M26
│   ├── Capmkt/                   # M27
│   ├── Openbank/                 # M23
│   ├── Account/                  # M29
│   ├── Cash/                     # M30
│   ├── Sanctkb/                  # M28
│   ├── Training/                 # M15
│   ├── Vendor/                   # M14
│   ├── Dashboard/                # M18
│   ├── Audit/                    # M19 (separate deployment)
│   ├── Evidence/                 # M19 vault
│   ├── Identity/                 # cross-cutting
│   └── Notify/                   # cross-cutting
├── resources/
│   ├── css/app.css               # Design-system tokens (A3 owns)
│   └── js/
│       ├── Components/           # A3 owns — design-system primitives
│       ├── Layouts/              # A3 owns — AuthenticatedLayout, GuestLayout, ExternalLayout
│       └── Pages/                # A3 owns — workbench pages (M07/M08/M09/M11/M12/M18/M19)
├── sidecars/
│   ├── aml-rules/                # A5 owns — Python FastAPI service
│   ├── workflow-worker/          # A5 owns — Temporal PHP SDK worker
│   └── go-screening/             # A5 owns — optional, conditional per ADR-016
├── infra/                        # A6 owns — Terraform + Helm + ArgoCD
│   ├── terraform/
│   ├── helm/
│   ├── argocd/
│   └── runbooks/
├── tests/                        # cross-cutting Pest tests; each module also has its own tests/ dir
├── database/migrations/          # cross-cutting migrations; module-specific live under modules/<Name>/Database/migrations/
├── routes/                       # global routes; module-specific in modules/<Name>/Routes/
├── config/
│   ├── atheris-rbac.yaml         # A1 owns — auto-generates Spatie policies
│   └── atheris.php
├── docs/                         # ADRs, integration specs, runbook indices
├── composer.json
├── package.json
├── tailwind.config.js            # A3 owns
└── .github/workflows/            # A6 owns — CI/CD
```

---

## 3. Workflow Norms — Every Agent

### 3.1 Branching and PRs

- Trunk-based development on `main`; feature branches `agent/<id>/<short-slug>` (e.g., `a2/m07-screening-service`).
- Conventional Commits (`feat:`, `fix:`, `refactor:`, `chore:`, `test:`, `docs:`, `perf:`, `ci:`).
- Squash merge only.
- PR template forces: BRD/AC IDs touched, NFRs affected, security implications, testing evidence, screenshots if UI.
- Required reviewers: the module owner (A2 unless infra) + one cross-team reviewer + the Foundation Agent (A1) on any structural change (modules registry, base middleware, audit chain, encryption cast, CI pipeline).

### 3.2 Testing Discipline

- **Pest 3** for PHP. `tests/Unit/`, `tests/Feature/`, `tests/Integration/`.
- **Playwright** for E2E on the React workbench; **Pest browser plugin** for Filament screens.
- **k6** for performance; budgets in TRD §14.1.
- **Pact** for contract tests between Laravel ↔ AML sidecar ↔ Temporal worker.
- Coverage gate: 80% global, 90% on financial-crime modules. Mutation gate: 70% global, 85% on financial-crime modules.

### 3.3 Security and Privacy Discipline

- Never copy production PII to non-prod without DPO sign-off + pseudonymisation.
- Never log PII (`Customer.full_name`, `dob`, identifier values, `Whistle.reporter_identity_blob`, STR contents). The `PiiRedactionProcessor` in Monolog is mandatory; do not bypass it.
- Never read `nfiu.str` without going through `StrAccessPolicy` — Postgres RLS will refuse, but defence-in-depth requires the application check too.
- Never use `eval`, `unserialize` on untrusted input, `extract` on request data, `assert` with strings.
- Never store auth tokens in `localStorage` in the React layer. Cookies only (Sanctum: sameSite=Strict, HttpOnly, Secure).
- Every external HTTP call goes through a Saloon connector with retries, circuit breaker, rate-limit, logging — never raw Guzzle in domain code.

### 3.4 Audit, Evidence, Retention

- Every state-changing handler dispatches an `AuditEvent` via the `EmitsAuditEvent` trait or explicit `AuditWriter::record(...)` call before returning a 2xx response. **If the audit write fails, the user action fails and rolls back.**
- Every evidentiary artefact (signed PDF, regulator submission XML, ack receipt, exported report) is sealed via `EvidenceVault::seal(...)` to S3 Object Lock with a retention class.
- Retention classes are defined in `config/atheris.php` per BRD §11.3; never override at call site.

### 3.5 Documentation Discipline

- Every module owns `modules/<Name>/README.md` (responsibility, public service surface, owned topics, owned tables, dependencies).
- Every ADR lives at `docs/adr/<NNNN>-<slug>.md`; numbered after `ADR-018` (which is the last in TRD v1.1).
- Every external integration owns `docs/integrations/<system>.md` (endpoint catalogue, auth, error cases, retry policy, sandbox vs prod URLs, contact).
- Every Temporal workflow owns `docs/workflows/<name>.md` with the state diagram (text or Mermaid) and activity contracts.
- OpenAPI specs live at `docs/openapi/<module>.yaml`; CI fails if they drift from the implementation.

### 3.6 Status Cadence

- Daily 15-minute stand-up: each agent reports yesterday/today/blockers to a shared `STATUS.md` at repo root, updated before 09:00 WAT.
- Weekly Friday read-out: each agent posts a short summary to `docs/weekly/YYYY-MM-DD.md` covering BRD modules advanced, AC IDs satisfied, open `[NEEDS DECISION]` items.
- Open decisions log: `docs/decisions/open.md` — anything blocking that needs a human (CCO, CISO, DPO, Head Eng) is logged here with a target date.

### 3.7 Escalation Triggers

Stop and escalate to the human owner (the project lead / Head of Engineering / CCO) when:

- A `[NEEDS DECISION]` blocks progress for more than 24 hours.
- A constraint in §0.3 appears to conflict with reality (e.g., a regulator endpoint cannot meet a performance budget).
- An NFR target is missed in two consecutive performance test runs.
- A security or privacy concern arises that has no precedent in the BRD/TRD (e.g., a vendor wants to receive PII outside the documented sub-processor register).
- A regulator publishes a new instrument materially changing scope (route to Horizon scanner workflow — but flag to humans for sign-off).

---

## 4. Per-Agent Briefs

Read your own section in full. Skim the other agents' charters so you know who to talk to.

---

### 4.1 Agent A1 — Foundation Agent

> **Charter:** Lay down a Laravel modular-monolith skeleton that A2/A3/A4/A5 can build into without re-litigating plumbing; own every cross-cutting concern (audit chain, encryption, Octane discipline, RBAC, base middleware, base CI). You are the load-bearing platform engineer.

#### Mission

You produce the **scaffold that nobody else has to think about again**. Cross-cutting concerns that touch every module — audit trail, field-level encryption, multi-tenancy scope, RBAC, idempotency, Octane safety, base CI — must work correctly before A2/A3/A4/A5 begin in earnest. You then continue to harden them across phases.

#### Inputs

- BRD v2.0 §1–§3, §11–§14 (data, NFRs, RBAC, integrations)
- TRD v1.1 Laravel §1–§9 (architecture), §13 (security), §17 (DevOps), §19 (migration)
- TRD Appendix B (sample DDL)
- Coordinate with A6 on infra (you do not build cloud infra — A6 does — but you bake CI and image stages)

#### Phase Plan (per TRD Appendix G)

| Phase | Deliverable |
|---|---|
| Phase 0 (week 1–2) | Repo skeleton; Laravel 11 LTS installed; `nwidart/laravel-modules` configured; module-stub generator command (`php artisan atheris:make-module <Name>`); base ServiceProvider; PgBouncer-aware DB config; Redis cluster config; OpenSearch Scout config; Octane (RoadRunner) baseline; SOPS-encrypted secrets convention; CI baseline (lint, PHPStan, Pest, Trivy) |
| Phase 0–1 | Base traits (`BelongsToTenant`, `EmitsAuditEvent`), base casts (`Encryptable`), base middleware (`OctaneScope`, `Audit`, `IdempotencyKey`, `EncryptionAccess`); RBAC config loader (`config/atheris-rbac.yaml` → Spatie permissions sync command); audit chain DDL + Postgres triggers; daily Merkle sealer job (skeleton); Identity module wired with Socialite → Keycloak |
| Phase 1 | Module scaffolds for all 30 BCs (empty modules with ServiceProvider, README, migrations dir, routes dir, tests dir); shared design tokens consumed from A3's `app.css`; Filament panel base registration |
| Phase 2 | Octane discipline tooling: custom PHPStan rule `Atheris\PHPStan\NoCrossModuleEloquent` (`ADR-017` enforcement) + `Atheris\PHPStan\OctaneStateLeakCheck`; `octane:check` integration in CI |
| Phase 3 | Temporal client wiring; workflow registry; `App\Workflows\Contracts\*` interfaces shared across modules |
| Phase 4–5 | Maker-checker pattern formalised as a `MakerCheckerService` + base policy traits; access recertification scheduled command |
| Phase 6+ | Performance hardening; OPcache + autoload tuning; final hand-off to A6 for prod cut-over |

#### Deliverables

- **Repo layout** under `atheris-platform/` exactly as §2.
- **Base packages installed and pinned** in `composer.json`: `laravel/framework:^11`, `laravel/octane`, `laravel/horizon`, `laravel/pulse`, `laravel/sanctum`, `laravel/passport`, `laravel/socialite`, `laravel/scout`, `filament/filament:^3`, `inertiajs/inertia-laravel`, `spatie/laravel-permission`, `spatie/laravel-data`, `spatie/laravel-model-states`, `spatie/laravel-activitylog`, `nwidart/laravel-modules`, `mateusjunges/laravel-kafka`, `temporalio/laravel-workflow`, `babenkoivan/elastic-scout-driver`, `maatwebsite/excel`, `barryvdh/laravel-dompdf`, `aws/aws-sdk-php`, `phpoffice/phppresentation`, `vladimir-yuldashev/laravel-queue-rabbitmq` (only if MSK not in use yet), `saloonphp/saloon:^3`.
- **Composer dev:** `pestphp/pest`, `pestphp/pest-plugin-laravel`, `pestphp/pest-plugin-faker`, `phpstan/phpstan`, `nunomaduro/larastan`, `vimeo/psalm`, `infection/infection`, `laravel/pint`, `rector/rector`, `mockery/mockery`.
- **Module stub command:** `php artisan atheris:make-module {Name}` creates `modules/<Name>/{Database/migrations, Filament, Http/Controllers, Jobs, Events, Listeners, Kafka, Models, Policies, Routes, Services, Tests}/`, README, ServiceProvider.
- **Base traits** (`app/Concerns/`): `BelongsToTenant`, `EmitsAuditEvent`, `Octanesafe`.
- **`Encryptable` cast** (`app/Casts/Encryptable.php`) per TRD §13.4 sample; KMS envelope; key-version stored in payload.
- **`audit.event` table** (TRD §8.2) + Postgres trigger that computes `this_hash` from `prev_hash || canonical_row_bytes`; trigger refuses UPDATE/DELETE.
- **Daily Merkle sealer:** `php artisan atheris:audit:seal-day` — skeleton + tests.
- **RBAC config:** `config/atheris-rbac.yaml` matching BRD §14.3 matrix; sync command `php artisan atheris:rbac:sync` produces Spatie roles/permissions.
- **Auth wiring:** Socialite → Keycloak; Sanctum SPA cookies; Passport client-credentials for partner APIs.
- **Octane (RoadRunner) Dockerfile** with worker tuning per TRD §5.4; `octane:check` in CI.
- **Base middleware:** `OctaneScope` (resets request-scoped bindings), `Audit` (wraps state-changing routes), `IdempotencyKey` (Redis-backed), `EncryptionAccess` (logs reads of PII fields).
- **CI baseline** (`.github/workflows/ci.yml`): Pint, PHPStan/Larastan level 9, Psalm, Pest with coverage gate 80%, Infection MSI gate, Trivy, Gitleaks, Snyk, OWASP DC. (A6 owns the deployment side; you own the *application-side* CI.)

#### Acceptance Criteria

- `composer install && php artisan octane:start` runs cleanly.
- `php artisan atheris:make-module Sandbox && php artisan migrate --path=modules/Sandbox/Database/migrations` succeeds and produces an empty but registered module with passing baseline tests.
- The audit chain test (`tests/Feature/Audit/HashChainTest.php`) inserts 1,000 events and verifies the chain integrity after random row attempts to UPDATE/DELETE (all rejected).
- An `Encryptable` cast round-trip test passes against a local KMS mock.
- `php artisan atheris:rbac:sync` reads the YAML and creates the BRD §14.3 roles/permissions in Spatie.
- The `NoCrossModuleEloquent` PHPStan rule fails the build on a synthetic violator under `tests/fixtures/violations/`.
- CI runs end-to-end in ≤15 minutes.

#### Hand-offs

- **To A2:** module scaffold + base traits + workflow registry + AuditWriter API.
- **To A3:** `resources/css/app.css` placeholder file you create; A3 fills the tokens. You install Tailwind + Vite + Inertia and wire the build.
- **To A4:** Filament panel base registration + auth integration; A4 owns resources.
- **To A5:** Saloon installed + the standard `ConnectorContract` interface in `app/Integrations/Contracts/`.
- **To A6:** application Docker image build stages; you do not build cluster infra.

#### Anti-patterns (Do NOT)

- Do not write business logic — that's A2.
- Do not build any module resource — that's A4 (admin) or A3 (workbench).
- Do not deploy to cloud — that's A6.
- Do not allow `ddl-auto`-style auto-migration on pod start; `php artisan migrate --force` only behind a release gate.
- Do not create cross-module Eloquent relationships in base code.
- Do not skip the daily Merkle sealer test even if integration with HSM is mocked.

---

### 4.2 Agent A2 — Backend Domain Agent

> **Charter:** Build every BRD module's backend (M01–M30): Eloquent models, services, jobs, events, Kafka producers/consumers, Temporal workflows, REST controllers, policies. You implement every functional rule and own the AC sign-off for the backend portion of every module.

#### Mission

Translate BRD §8 (Detailed Feature Specifications) and TRD §10 (Module-by-Module Technical Design) into working Laravel modules. Every `FR<m>.<n>` is one or more PHP classes; every `AC<m>.<n>` is one or more Pest tests. You do not build UI; A3 and A4 do. You do not build connectors; A5 does. You consume them.

#### Inputs

- BRD §7 (Functional Requirements — Modules) and §8 (Detailed Feature Specifications)
- TRD §4.2 (BCs → Laravel modules), §7–§8 (data model), §10 (per-module technical design), §11 (integration patterns from the *caller* side)
- A1's module scaffolds + base traits + workflow registry
- A5's Saloon connector contracts (you call them through interfaces; A5 implements them)

#### Phase Plan

| Phase | BRD Modules |
|---|---|
| Phase 1 (Regulatory Backbone) | M01, M02, M03, M17, M18 (basic), M19 |
| Phase 2 (Risk & Monitoring) | M04, M05, M28 |
| Phase 3 (Financial Crime) | M06, M07, M08, M09 |
| Phase 4 (Returns & Reporting) | M10, M21 (tax), M18 (full) |
| Phase 5 (Consumer / Conduct / Governance) | M11, M13, M22, M24, M25 |
| Phase 6 (Data / Cyber / Vendor) | M12, M14, M16 |
| Phase 7 (Capital / Cross-border / ESG / Open Banking / Account / Cash) | M20, M23, M26, M27, M29, M30, M15 |

#### Deliverables (Per Module)

For each of the 30 modules, deliver under `modules/<Name>/`:

- **`Models/`** — Eloquent models matching TRD §7.3 / §8 DDL. PII columns use `Encryptable` cast (A1's). Models use `BelongsToTenant` + `EmitsAuditEvent` traits where applicable. No cross-module relationships.
- **`Services/`** — public service classes (the only legal entry point from other modules). E.g., `LibraryService` exposes `findInstrument`, `searchUniverse`, `importInstruments`.
- **`Http/Controllers/`** — REST controllers; every action is `__invoke`-style or thin and delegates to a service. Form requests for validation. ProblemDetail errors.
- **`Routes/api.php`** + (where Filament needed) `Routes/web.php` (A4 will register Filament resources).
- **`Jobs/`** — Horizon-queued jobs (idempotent, deduplicated via `Cache::lock`). Each job picks its supervisor (`library`, `aml-bridge`, `returns`, `audit`, etc.).
- **`Events/` and `Listeners/`** — domain events; consumer side calls services.
- **`Kafka/`** — producers and consumers (`mateusjunges/laravel-kafka`). One consumer group per Horizon supervisor.
- **`Workflows/`** — Temporal workflow definitions + Activities interfaces. Activities implemented in `Services/Activities/`. Registered with the Temporal worker in `sidecars/workflow-worker/`.
- **`Policies/`** — Laravel policies wired to Spatie permissions; explicit gate for every state-changing route (maker-checker requires two policy checks: `canDraft` AND `canApprove`).
- **`Database/migrations/`** — Liquibase-style sequenced migrations matching TRD §8 DDL. Use raw SQL inside `DB::statement()` for CHECK constraints, partial indexes and triggers Eloquent migrations can't express cleanly.
- **`Tests/Unit/`, `Tests/Feature/`, `Tests/Integration/`** — Pest tests; each `AC<m>.<n>` has a test labelled `it('satisfies ACM<m>.<n>', ...)`.
- **`README.md`** — purpose, owned tables, owned topics, public service surface, dependencies on other modules (via service interfaces only).

#### Module-Specific Highlights (Read Each Before Starting)

| Module | Notes |
|---|---|
| **M01 Library** | Horizon scanner: per-regulator strategy classes implementing `HorizonSource`. OCR via A5's `TextractConnector`; Tesseract fallback via queued shell job. Universe search via Scout → OpenSearch. Bulk import via `maatwebsite/excel` + chunked Horizon jobs. |
| **M02 Policy** | Spatie state machine; `PolicyAnnualReviewWorkflow` Temporal workflow with 60/30/7-day timers. Watermarked PDF via LibreOffice queued render. |
| **M03 Obligations** | Lives in `Library` module (obligations belong to instruments). Materialised view `vw_obligation_heatmap` refreshed every 15 minutes. |
| **M04 RCSA/BRA** | `RiskScoringService` with 3×3 and 5×5 matrices; `BraCycleWorkflow` 30-day SLA. |
| **M05 Controls + Monitoring** | CCM rules each implement `CcmRule`. Scheduled via `Schedule::command()`. Sampling: `SampleSizeCalculator` per AICPA / ISA 530. Control failure → `Issue` row inside the same transaction. |
| **M06 KYC** | Octane-mode `OnboardCustomerController` fans out BVN+NIN+CAC+screening with Guzzle promises, 2.5s budget. PII via `Encryptable`. PSC table with 5% threshold. |
| **M07 Screening** | Lives in `Screening` module deployed as `atheris-octane-screening` profile. Hot list in Redis; Matchers (`ExactMatcher`, `LevenshteinMatcher`, `JaroWinklerMatcher`, `SoundexMatcher`, `MetaphoneMatcher`, `YorubaPhoneticMatcher`, `HausaPhoneticMatcher`, `IgboPhoneticMatcher`). TFS 24-hour reporting via Temporal timer. |
| **M08 AML** | Proxy module only — produces `txn.ingested`, consumes `alert.raised` from A5's Python sidecar. Tuning workbench writes are audited and maker-checker enforced. |
| **M09 NFIU goAML** | XML build via PHP DOM + XSD validation. `StrAccessPolicy` defence-in-depth + Postgres RLS. Tipping-off audit logged. |
| **M10 Returns** | `ReturnDefinition` carries a `DraftStrategy` per return; strategies per regulator portal (NFIU, FIRS, CBN, NDPC, SEC, PenCom, NDIC). Maker-checker via policies. |
| **M11 Complaints** | `ComplaintSlaWorkflow` 14-day CBN SLA; CCMS connector. |
| **M12 DPO** | `BreachNotificationWorkflow` 72-hour timer with 24h/48h reminders, escalation on breach. RoPA, DPIA, DSR ticketing. |
| **M13 Whistle** | `whistle.reporter_identity_blob` via `pgcrypto` with key restricted to Ethics Officer + BAC Chair. No HRIS joins. |
| **M14 Vendor** | Cert-expiry scheduled job 60/30/7 days pre-expiry. |
| **M15 Training** | LMS connector (A5); enrolment triggers on HRIS joiner events. |
| **M16 Incident** | Regulator timers in Temporal: 4h/24h CBN; 72h NDPC; SEC; FRC. |
| **M17 Calendar** | Read model populated by event subscriptions on `*.due` topics. ICS export via signed URL. |
| **M18 Dashboard** | Materialised views + Reverb broadcasts for live counts. `BoardPackBuilderJob` uses `phpoffice/phppresentation`. |
| **M19 Audit** | A1 owns the chain; you own the `EmitsAuditEvent` consumers' wiring and the Internal Audit read endpoints. |
| **M20 FATCA/CRS** | IDES XML + S/MIME via Symfony Mime. |
| **M21 Tax** | TaxPro Max via A5's Saloon connector. |
| **M22 Conduct** | Declarations, gifts, COI, asset declarations; surveillance alert ingest via Kafka. |
| **M23 Open Banking** | Consent revocation effective within minutes (propagates via `consent.revoked`). |
| **M24 ABAC** | `EfccFreezingOrderWorkflow` Temporal workflow: intake → core-banking freeze API → MLRO/Legal review → response. 30-min target end-to-end. |
| **M25 Governance** | `FitAndProperWorkflow` includes CBN no-objection touchpoint; webhook completion. |
| **M26 ESG** | Scope 1/2/3 GHG schema; NSBP/NCCC/FRC SDS return strategies. |
| **M27 Capital Market** | Insider-list closed-window enforcement via `TradingSystemBlockConnector` (A5). |
| **M28 Sanctions KB** | Materialised view `vw_penalty_exposure`. Day-1 load of 417 lines. |
| **M29 Account Mgmt** | Nightly dormant classifier; e-Dividend; dud-cheque register. |
| **M30 Cash** | Counterfeit workflow; Clean Note Policy; ATM KCI ingest. |

#### Acceptance Criteria

For each module, **every `AC<m>.<n>` in the BRD has a passing Pest test labelled `it('satisfies AC<m>.<n>', ...)`**. Module README lists which AC IDs are covered and links to the tests.

#### Hand-offs

- **From A1:** module scaffold + base traits + workflow registry.
- **From A5:** Saloon connector implementations matching the contracts you declare.
- **To A3:** for workbench-served modules (M06, M07, M08, M09, M11, M12, M18), publish a stable JSON contract via Inertia props or REST; coordinate page wireframes with A3.
- **To A4:** Eloquent models, policies, services. A4 builds Filament resources on top of these.
- **To A6:** module-specific Kafka topic config; HPA hints; SLO definitions per service.

#### Anti-patterns

- Do not call another module's Eloquent model directly. Use its public Service.
- Do not skip the `EmitsAuditEvent` trait on a state-changing model.
- Do not put rule evaluation in PHP for AML — that's the Python sidecar.
- Do not put long-running orchestration (>30s) in a Horizon job — use Temporal.
- Do not store PII unencrypted; do not log PII.
- Do not bypass maker-checker via direct DB writes.

---

### 4.3 Agent A3 — UI / Frontend Agent (Design System Owner)

> **Charter:** Build the entire user-facing layer that isn't Filament: design-system tokens, the Inertia + React component library, the three layouts (`AuthenticatedLayout`, `GuestLayout`, `ExternalLayout`), and the workbench pages (M06 CDD, M07 alert workbench, M08 case workbench, M09 STR/CTR, M11 complaints triage, M12 DPO console, M18 dashboards, M19 audit console). The **AuditPro GRC Design System** is your specification.

#### Mission

Translate `AuditPro_GRC_Design_System.md` into production React components and pages that meet the design system's accessibility and visual standards exactly. Build the components A4 (Filament) will not provide and the workbench pages A2's modules need. Maintain WCAG 2.1 AA throughout.

#### Inputs

- `AuditPro_GRC_Design_System.md` — the entire document is your spec.
- BRD §4.2 (personas) and §8 (per-module functional rules — for what each workbench needs to do)
- TRD §10 (per-module technical design — for the Inertia controllers each page consumes)
- A1's scaffold: Vite, Inertia, Tailwind, React 18, TypeScript already installed
- A2's Eloquent models and service surfaces

#### Phase Plan

| Phase | Deliverables |
|---|---|
| Phase 0 | CSS tokens (Section 2 of design system) → `resources/css/app.css`. Tailwind config extension. Inter + Roboto Mono font loading. Scrollbar customisation. Keyframe `bell-ring`. |
| Phase 0–1 | Layouts: `AuthenticatedLayout` (sidebar 260/72px collapsed; topbar 64px; sticky), `GuestLayout`, `ExternalLayout`. Sidebar nav active state with left border `var(--color-accent)`. |
| Phase 1 | Component primitives (Section 6 of design system): `PrimaryButton`, `SecondaryButton`, `DangerButton`, `IconButton`, `TextInput`, `InputLabel`, `InputError`, `Checkbox`, `Select`, `StatusBadge`, `RatingBadge`, `Card`, `StatCard`, `DataTable`, `Pagination`, `FilterBar`, `Modal`, `ConfirmDialog`, `PageHeader`, `EmptyState`, `FlashNotification`, `Dropdown`, `NavLink`. |
| Phase 1 | Chart components: `DonutChart`, `ProgressRing`, `ProgressBar`. Use ECharts under the hood where complex; SVG for ring/bar. |
| Phase 1–2 | Specialised components: `AiAssistantPanel`, `AiReportGenerator`, `DuplicateFindingWarning`, `GenerateExternalLinkModal`, `RegulatoryReferenceSuggester`. |
| Phase 3 | M06 CDD/EDD workbench, M07 alert workbench, M08 case workbench, M09 STR/CTR drafting. |
| Phase 4 | M18 dashboards: replicate the Compliance Risk Profile pivots from the source workbook as live ECharts widgets. |
| Phase 5 | M11 complaints triage, M12 DPO console. |
| Phase 6 | M19 audit / Internal Audit read console (no PII, watermarked exports). |
| Phase 8 | Phase-2 regulator portal (read-only Inertia views). |

#### Deliverables

- **Design tokens** in `resources/css/app.css` exactly matching Section 2.1 of the design system. All 15 color tokens, plus reusable CSS classes (`.card`, `.badge`, `.form-input`, `.form-label`, `.form-select`, `.progress-bar`, `.progress-bar-fill`, `.page-title`, `.page-header`, `.filter-bar`, `.filter-group`, `.data-table`, `.sidebar`, `.sidebar-nav-item`, `.stat-card`).
- **Tailwind config** (`tailwind.config.js`) extending the font family with Figtree → Inter fallback; enabling `@tailwindcss/forms`.
- **Components** under `resources/js/Components/` — each `.jsx` matching the spec in Section 6 of the design system (exact class names, exact transitions, exact ARIA attributes).
- **Layouts** under `resources/js/Layouts/` — `AuthenticatedLayout.jsx` (sidebar + topbar + mobile drawer), `GuestLayout.jsx`, `ExternalLayout.jsx`.
- **Page templates** under `resources/js/Pages/` — one for each workbench (CDD, ScreeningWorkbench, AmlCaseWorkbench, StrDrafting, ComplaintsTriage, DpoConsole, CcoDashboard, BoardDashboard, AuditConsole, RegulatorPortal).
- **Composition recipes** (Section 11 of design system) applied to every page.
- **Accessibility:** axe-core + Pa11y CI checks passing on every page.
- **Storybook (optional but recommended)** for every component primitive.

#### Acceptance Criteria

- Every component listed in Section 6 of the design system exists under `resources/js/Components/` and matches the spec's exact classes, transitions and ARIA.
- `axe-core` CI returns zero serious or critical violations.
- The CCO dashboard reproduces the four Drafts KPIs (Total Obligations 352, Regulators 43, High-Risk 215, Areas of Focus 29) and the four pivots (Nature, Item Type, Regulator, Risk Rating) as live ECharts widgets sourced from `dashboard-service` (BRD §13.5; TRD §10.18).
- M07 alert workbench supports keyboard-only operation end-to-end (alert triage → decision → audit-event capture).
- Color contrast checked: all `text-gray-500` on white passes AA Large; all `text-white` on `var(--color-primary)` passes AA Normal.
- Mobile breakpoints behave per design system Section 9.

#### Hand-offs

- **From A1:** Vite + Inertia + Tailwind base; `app.css` placeholder.
- **From A2:** Inertia props contracts for each workbench page; coordinate JSON shape.
- **To A4:** the design tokens in `app.css` are loaded by Filament too; coordinate so Filament theme picks them up.

#### Anti-patterns

- Do not invent new color values. If you need a color, use either a CSS custom property or a Tailwind class from the design system.
- Do not use `localStorage` for tokens.
- Do not skip focus rings on interactive elements (Section 12 of design system).
- Do not introduce a UI library other than what's allowed (Headless UI, Heroicons, ECharts, Tailwind UI patterns). No Material UI, no Chakra, no Bootstrap.
- Do not assume desktop only — every page must respond per Section 9.
- Do not display PII (`Customer.full_name`, `dob`, identifier value, STR content) without a server-side authorisation that returns the data; the frontend must never decrypt.

---

### 4.4 Agent A4 — Filament Admin Agent

> **Charter:** Build Filament 3 resources, widgets, actions and bulk-import flows for every CRUD-heavy module. You are the admin-UI productivity engine; you ship the screens compliance officers spend most of their day in.

#### Mission

For every BRD module that is primarily CRUD with workflow side-effects, provide a Filament resource backed by A2's Eloquent models and services. Maker-checker is enforced via Filament actions wired to Spatie policies. Bulk import (especially Day-1) is a Filament action invoking A2's chunked Horizon jobs.

#### Inputs

- BRD §8 — for each module, the fields, the workflow states, the validation rules
- TRD §10 — for each module, the Filament-specific design intent
- A2's Eloquent models, services, policies, state machines
- A3's `app.css` design tokens — Filament theme consumes them

#### Phase Plan

| Phase | Filament Resources |
|---|---|
| Phase 1 | `InstrumentResource` (M01), `ObligationResource` (M03), `PolicyResource` (M02), `MonitoringActivityResource` (M05), `ReturnDefinitionResource` (M10), `ReturnRunResource` (M10), `CalendarWidget` (M17) |
| Phase 2 | `CrmpRowResource` (M04 × 12 themes), `ControlResource` (M05), `KciKriWidget` (M05), `SanctionLineResource` (M28) |
| Phase 3 | `CustomerResource` (M06, no-PII columns by default; "Reveal" action audits the read), `AlertResource` (read-only — A3 owns the workbench), `StrResource` (read-only) |
| Phase 4 | `ReturnRunResource` enhancements, `KpiSnapshotWidget` (M18) |
| Phase 5 | `ComplaintResource` (M11), `WhistleResource` (M13, Ethics-Officer-only panel), `GiftRecordResource` (M22), `CoiResource` (M22), `AbacFreezingOrderResource` (M24), `BoardMemberResource` (M25), `RptResource` (M25), `FitAndProperResource` (M25) |
| Phase 6 | `RoPaResource` (M12), `DpiaResource` (M12), `DsrResource` (M12), `BreachResource` (M12), `VendorResource` (M14), `VendorAssessmentResource` (M14), `VendorCertificateResource` (M14), `IncidentResource` (M16) |
| Phase 7 | `EsAssessmentResource` (M26), `InsiderListResource` (M27), `DisclosureResource` (M27), `ConsentResource` (M23), `DormantRecordResource` (M29), `DudChequeResource` (M29), `CounterfeitEventResource` (M30), `BankChargeResource` (M29), `TrainingModuleResource` (M15), `EnrolmentResource` (M15), `FatcaClassificationResource` (M20), `TaxReturnResource` (M21) |

#### Deliverables (Per Resource)

For each Filament resource:

- **`modules/<Name>/Filament/Resources/<Entity>Resource.php`** — extends `Filament\Resources\Resource`; form schema; table columns; filters; actions; bulk actions; header actions (e.g., bulk import).
- **Pages** — `List<Entity>`, `Create<Entity>`, `Edit<Entity>`, `View<Entity>` under `Filament/Resources/<Entity>Resource/Pages/`.
- **Widgets** — KPI cards, recent-events tables, charts (using A3's ECharts components via Filament chart widgets where possible).
- **Custom actions** — workflow triggers (e.g., "Submit Return", "Approve Policy") that call A2's services and respect maker-checker.
- **Authorisation** — every resource has a `getEloquentQuery()` that respects Spatie permissions; every action checks the relevant policy.
- **Tests** — Filament resource tests via Pest + Filament's testing helpers.

#### Specific Filament Patterns to Honour

- **Theme:** import A3's `app.css` tokens into the Filament panel via `FilamentColor::register()` and the custom theme CSS. The navy `var(--color-primary)` is the primary; the gold `var(--color-accent)` is the active-nav indicator.
- **Bulk import:** every CRUD list has a "Bulk import (XLSX)" header action accepting an XLSX upload and dispatching a Horizon job via A2's `BulkImport<Entity>Job`. Live progress via Reverb broadcast.
- **Sensitive data:** for `CustomerResource`, PII columns are hidden by default; a "Reveal" action requires a reason and audits the read via `EncryptionAccess` middleware. For `WhistleResource`, the panel is restricted to Ethics Officer + BAC Chair via Filament tenancy; reporter identity is never shown in table columns.
- **Maker-checker:** state transitions are surfaced as actions; the policy decides whether the current user can draft, approve, or both. Self-approval is forbidden.
- **Exports:** every list has an export action producing an XLSX via `maatwebsite/excel` and an evidence record (sealed PDF for board-pack contexts).

#### Acceptance Criteria

- Every CRUD-heavy module (28 of 30) has a Filament resource with create/list/edit/view pages.
- Bulk import for M01 (Instruments), M03 (Obligations via instruments), M04 (CRMP rows), M05 (Monitoring), M10 (Returns), M28 (Sanctions) honours the Drafts schema and produces the same migration report A6's loader produces.
- Filament-side authorisation tests verify maker-checker for at least M02 (Policy approval), M10 (Return submission), M25 (Fit-and-proper approval).
- The Filament UI passes the same axe-core checks A3's UI does.
- "Reveal" action on `CustomerResource` produces an `audit.event` row visible in M19.

#### Hand-offs

- **From A2:** Eloquent models, services, policies, state machines, Horizon jobs.
- **From A3:** Tailwind tokens; design-system-aligned theme; chart components if Filament's defaults are insufficient.
- **To A6:** seed admin user roles + initial Filament panel access bootstrap.

#### Anti-patterns

- Do not access the database directly from a Filament action; go through A2's services so audit, idempotency and policies fire.
- Do not display PII unless a Reveal action with reason capture fires.
- Do not introduce a Filament plugin without a security review.
- Do not bypass maker-checker.

---

### 4.5 Agent A5 — Integrations Agent

> **Charter:** Build every external system connector (regulator + bank-internal), the Python AML sidecar, the Temporal workflow worker, and the optional Go screening service. You are the team's contract negotiator with the outside world.

#### Mission

Every external system the platform talks to gets a typed Saloon connector implementing the contract A2 declares. Connectors handle retries, circuit-breakers, rate-limits, error decoding, sandbox/prod URL switching, and logging. The Python AML sidecar replaces Drools; it consumes Kafka and emits alerts. The Temporal worker runs the workflows A2 defines. The optional Go service is a fallback per `ADR-016`.

#### Inputs

- BRD §12 (Integration Requirements)
- TRD §11 (Integration Architecture) and §10 (per-module integration touchpoints)
- A2's declared `ConnectorContract` interfaces under `app/Integrations/Contracts/`
- Vendor documentation (NIBSS, NIMC, CAC, FIRS, NIS, FRSC, NFIU goAML XSD, eFASS, CBN portals, NDPC portal, SEC e-portal, PenCom RBS, SWIFT, sanctions vendors)

#### Phase Plan

| Phase | Connector / Sidecar |
|---|---|
| Phase 0 | `app/Integrations/Contracts/*` interfaces (with A2). Saloon scaffold. Standard middleware (logging, retry, circuit-breaker). Sandbox/prod URL discriminator. |
| Phase 1 | `KeycloakConnector` (federation). Document `docs/integrations/keycloak.md`. |
| Phase 1–2 | Internal: HRIS SCIM consumer; core-banking CDC Kafka consumer; AD/Azure AD OIDC bridge. |
| Phase 2 | `SanctionsVendorConnector` interface + adapters for Dow Jones, Refinitiv, LexisNexis, Accuity (pick one or two per OD-4); NIBSS Watch-list (daily file via SFTP). |
| Phase 3 | `NibssBvnConnector`, `NimcNinConnector`, `CacConnector`, `FirsTinConnector`, `NisPassportConnector`, `FrscLicenceConnector`, `InecPvcConnector`. AML sidecar Python service. |
| Phase 3 | `NfiuGoAmlConnector` (HTTPS for submission API + SFTP fallback via `phpseclib/phpseclib`). XSD validator. |
| Phase 4 | `CbnEfassConnector` / `CbnEfassSftpStrategy`; `FirsTaxProMaxConnector`; `NdpcConnector`; `SecEPortalConnector`; `PenComRbsConnector`; `NdicConnector`; `ScumlConnector`. |
| Phase 5 | LMS connector (M15); voice/SMS intake (M13); EFCC freezing-order ingress + core-banking freeze egress (M24). |
| Phase 6 | NDPC reporting portal (M12); NCC SMS (consumer notifications). |
| Phase 7 | FATCA IDES (S/MIME); OECD CRS via FIRS; SWIFT bridge; NIBSS NIP/RTGS bridge; trading-system block API (M27); facilities/credit-book GHG ingest (M26). |
| Phase 0+ | **Temporal worker:** `sidecars/workflow-worker/` — PHP 8.3 + Temporal SDK; registers every workflow A2 ships. |
| Phase 0+ | **AML sidecar:** `sidecars/aml-rules/` — Python 3.12 + FastAPI + Pydantic + Kafka-Python. Consumes `txn.ingested`, evaluates rules, emits `alert.raised`. |
| Phase 0 (conditional) | **Go screening:** `sidecars/go-screening/` — only if Phase-0 k6 misses the 500ms p95 budget on Octane. Same JSON contract as `atheris-octane-screening`. |

#### Deliverables (Per Connector)

For each connector:

- **Saloon Connector class** under `app/Integrations/<System>/<System>Connector.php`.
- **Saloon Request classes** for each endpoint.
- **DTOs** via `spatie/laravel-data`.
- **Retry / circuit-breaker / rate-limit** middleware composed via Saloon's middleware pipeline.
- **mTLS configuration** where the system requires it (NIBSS, eFASS, SWIFT, FATCA IDES). Certificates from Vault.
- **Sandbox vs prod URL** via `config/integrations.php` per environment.
- **Saloon MockClient fixtures** for tests; recorded fixtures via `saloon` cassettes.
- **`docs/integrations/<system>.md`** — endpoints, auth, errors, retry policy, sandbox URL, prod URL, contact, escalation path.
- **Pact contract test** with A2's consuming service.

#### Specific Deliverable Notes

- **NFIU goAML:** XML built via PHP DOM; XSD validation pre-submission via `libxml`. Submission HTTPS (with mTLS cert from Vault) primary; SFTP fallback for batch. Acknowledgement parser.
- **FIRS TaxPro Max:** REST + SOAP modes; signed payloads.
- **SWIFT:** the bank's existing SWIFT gateway exposes a Kafka topic; you consume it for payment screening. You do not talk to SWIFTNet directly.
- **CBN eFASS / FinA / RBS:** HTTPS where API exposed; otherwise SFTP upload with ack-capture.
- **NDPC:** REST + manual web-form fallback for breach notifications.
- **AML sidecar (Python):**
  - FastAPI service hosting `/healthz`, `/rules` (CRUD with maker-checker via JWT from Keycloak), `/score` (synchronous scoring for one txn).
  - Kafka consumer for `txn.ingested`, producer for `alert.raised`.
  - Rule DSL with `business-rules` or a custom DSL via `lark`.
  - Behavioural baseline service using Redis-backed rolling windows.
  - Containerised; tested with Pact against A2's `aml` module.
- **Temporal worker (PHP):**
  - Registers every workflow declared in `modules/<Name>/Workflows/`.
  - Activities resolved from each module's `Services/Activities/`.
  - Task queue per workflow type (`policy-review`, `breach-72h`, `fit-and-proper`, `efcc-freezing-order`, `bra-cycle`, `complaint-sla`, `return-approval`, `regulatory-change`, `vendor-cert-expiry`, etc.).
- **Go screening (optional):**
  - Same JSON contract as Octane screening.
  - Same audit-write semantics (synchronous before return).
  - Built only if `ADR-016` triggers.

#### Acceptance Criteria

- Every external system in BRD §12 has a typed Saloon connector + sandbox tests + a Pact contract against A2.
- NFIU goAML XML passes XSD validation in 99%+ of attempts on the test corpus.
- AML sidecar processes the Phase-0 synthetic stream (10k txns/min) without lag.
- Temporal worker registers all workflows on boot; restart drill loses zero workflow state.
- If `ADR-016` triggers: Go screening service hits p95 ≤300ms under load.

#### Hand-offs

- **From A2:** `ConnectorContract` interfaces; clear input/output expectations.
- **To A2:** working implementations + mocks for unit tests.
- **To A6:** Helm charts and ArgoCD manifests for the sidecars.
- **To DPO (human):** sub-processor list update if a new vendor is introduced.

#### Anti-patterns

- Do not put business logic inside connectors. Connectors translate; A2 decides.
- Do not hard-code credentials. Vault only.
- Do not skip the Pact test. Drift between A5 implementation and A2 contract is the #1 integration failure mode.
- Do not call sandbox URLs from prod or vice versa.
- Do not log raw payloads containing PII; redact via Monolog's `PiiRedactionProcessor`.

---

### 4.6 Agent A6 — DevOps / SRE / Migration / QA Agent

> **Charter:** Build the cloud landing zone, deploy the platform, run the observability and security stack, automate CI/CD, execute the Day-1 data migration from the source XLSM, run performance and DR drills, and own production readiness gates.

#### Mission

Stand up AWS af-south-1 active-active with NG edge for production data, NG Tier-III for HSM and DR copies. Deploy A1's image + A2's modules + A3's UI + A4's Filament + A5's sidecars onto EKS via ArgoCD. Run the observability stack. Execute the Day-1 load. Run performance tests against every NFR target. Drill DR. Sign off go-live.

#### Inputs

- BRD §9 (NFRs), §11 (data retention), §12 (integrations)
- TRD §5 (deployment), §14 (NFR implementation), §15 (DR), §16 (observability), §17 (CI/CD), §18 (runbooks), §19 (Day-1 migration), §20 (platform compliance)
- A1's application Docker images
- A5's sidecar images
- The Drafts XLSM in `/uploads/Compliance Management Toolkits DRAFT.xlsm` for Day-1 load

#### Phase Plan

| Phase | Outcome |
|---|---|
| Phase 0 (week 1–4) | AWS landing zone via Terraform: organisations, accounts (prod / staging / dev / dr), VPCs, EKS clusters, MSK, Aurora, OpenSearch, ElastiCache, S3 with Object Lock, CloudHSM cluster, Vault, Keycloak HA, Istio, Kong, SPIRE. ArgoCD bootstrapped. CI/CD pipeline executable end-to-end on a hello-world Laravel image. Observability stack (Prometheus + Thanos + Loki + Tempo + Grafana + Pulse) live. |
| Phase 1 | Per-service Helm charts for `atheris-app`, `atheris-octane-screening`, `atheris-app-horizon`, `atheris-app-scheduler` (leader-elected), `atheris-audit-writer`. ArgoCD App-of-Apps. Secrets via Vault Agent. mTLS via Istio. |
| Phase 1–2 | Day-1 loader: read every sheet of the Drafts XLSM via `maatwebsite/excel`, chunk to Horizon jobs, populate reference tables → instruments (352) → CRMP rows (1,227+) → monitoring (236) → returns (187) → sanctions (417). Idempotent re-run; migration report XLSX. |
| Phase 3 | Sidecar deployments: `atheris-aml-rules` (Python), `atheris-workflow-worker` (Temporal), optional `atheris-go-screening`. Temporal cluster (Cassandra/Postgres backend) self-hosted. |
| Phase 4 | NFR test harness: k6 profiles for screening, onboarding, AML, returns, dashboard. Soak tests (24h). Chaos drills (Litmus). Pact CI gates. |
| Phase 5–7 | Module rollouts: blue/green for stateless; canary 5%→25%→100% for `octane-screening` and `aml-rules`. |
| Phase 8 | Pen-test by CBN-recognised firm; CBN cyber self-assessment evidence pack; final go-live readiness review. |

#### Deliverables

- **Terraform** (`infra/terraform/`) for AWS landing zone: VPCs, EKS, MSK, Aurora, OpenSearch, ElastiCache, S3 (with Object Lock Compliance), CloudHSM, Vault, Route 53, AWS WAF.
- **Helm charts** (`infra/helm/`) for each application: `atheris-app`, `atheris-octane-screening`, `atheris-app-horizon`, `atheris-app-scheduler`, `atheris-audit-writer`, `atheris-aml-rules`, `atheris-workflow-worker`, optional `atheris-go-screening`.
- **ArgoCD** (`infra/argocd/`) App-of-Apps; auto-sync for dev; manual approval for staging and prod.
- **CI/CD** (`.github/workflows/`) per TRD §17.2: lint, PHPStan/Larastan level 9, Psalm, Pest with coverage gate, Infection MSI gate, Snyk, OWASP DC, Gitleaks, Trivy, Cosign signing, Syft SBOM, integration tests via Testcontainers-PHP, Pact, e2e Playwright + k6 smoke, deploy-dev, manual approval → staging → prod.
- **Day-1 loader runbook** (`infra/runbooks/day1-load.md`) + the actual Laravel command + the Horizon supervisor + the migration report exporter.
- **Observability** stack: Prometheus + Thanos for metrics; Loki + Promtail/Vector for logs (90-day hot + 5-year cold via S3 Object Lock); Tempo + OpenTelemetry for traces; Grafana with the dashboards from TRD §16.4; Laravel Pulse for application-level insight.
- **Alerting** (`infra/runbooks/alerts.md`) — SLO burn-rate alerts; business alerts (overdue returns, overdue STR, stale sanctions list, NDPC 72h timer <24h remaining).
- **Runbooks** (`infra/runbooks/`) — R-001 service down, R-002 Aurora failover, R-003 Kafka broker loss, R-004 sanctions list stale, R-005 goAML submission failure, R-006 CBN cyber incident, R-007 NDPC breach trigger, R-008 EFCC freezing order, R-009 regulator examination support, R-010 DR failover, R-011 Octane worker memory leak, R-012 Horizon supervisor stalled.
- **DR drill schedule** (`infra/runbooks/dr-tests.md`) — quarterly tabletop, annual live failover, quarterly backup restore.
- **Performance test plan** (`infra/perf/k6/`) — scripts for screening, onboarding, AML pipeline, returns submission, dashboard.
- **Compliance evidence pack templates** (`infra/compliance/`) — CBN Risk-Based Cybersecurity Framework self-assessment template, NDPA RoPA for the platform itself, ISO 27001 statement of applicability.

#### Acceptance Criteria

- `terraform apply` produces the full landing zone reproducibly.
- ArgoCD reconciles a clean install end-to-end with zero manual steps.
- Day-1 loader populates: 43 regulators, 13 instrument types, 29 areas of focus, 3+ statuses, 4 nature values, 3 risk ratings, 12 CRMP themes, 352 instruments, ≥1,227 CRMP rows, 236 monitoring activities, 187 returns, 417 sanctions — exactly the counts in BRD §S11–S14.
- The migration report XLSX enumerates every row with target table + target ID + validation status.
- k6 against the staged environment hits every NFR target in TRD §14.1 for two consecutive monthly observation windows before prod cut-over.
- Live DR failover completes within RTO ≤4h with RPO ≤15m demonstrated by data integrity checks post-failover.
- CBN cyber self-assessment evidence pack is signed off by the CISO.
- NDPC platform-side RoPA is signed off by the DPO.

#### Hand-offs

- **From A1:** application Docker images, CI-pipeline definitions.
- **From A5:** sidecar Docker images, integration credentials list.
- **From A2/A4:** deploy-time configuration (env vars, feature flags, queue connections).
- **To the bank's SRE team:** runbooks, alert routes, on-call rota.
- **To the CCO/CISO/DPO (humans):** the readiness evidence pack and the go-live sign-off.

#### Anti-patterns

- Do not deploy with `php artisan migrate --force` running automatically on pod start.
- Do not allow prod data into non-prod environments without DPO sign-off and pseudonymisation.
- Do not let any service skip the `OctaneScope` middleware in prod.
- Do not cut DR drill cadence. Quarterly is the floor.
- Do not put HSM keys outside Nigeria.
- Do not let any S3 bucket avoid Object Lock for evidentiary classes.
- Do not deploy a release that lowers a SLO without an explicit waiver from the CCO.

---

## 5. Phase Sequencing — How the Six Agents Move Together

| Phase | Calendar (indicative) | A1 Foundation | A2 Backend | A3 UI | A4 Filament | A5 Integrations | A6 DevOps |
|---|---|---|---|---|---|---|---|
| 0 | weeks 1–4 | Repo, scaffold, base traits, audit chain, RBAC, encryption cast, Octane, CI baseline | (waiting on A1) | CSS tokens, layouts, primitives | (waiting on A1 + A2) | Connector interfaces, Saloon scaffold, Keycloak, Temporal cluster bootstrap | AWS landing zone, EKS, MSK, Aurora, OpenSearch, ArgoCD, observability |
| 1 | weeks 5–14 | Module scaffolds, RBAC sync, Octane discipline lint | M01, M02, M03, M17, M19 | Components Phase 1 + AuthenticatedLayout | M01/M02/M03 resources | Keycloak federation; HRIS SCIM; core-banking CDC consumer | Helm charts; ArgoCD App-of-Apps; CI green |
| 2 | weeks 15–24 | Maker-checker service; Temporal client wiring | M04, M05, M28 | Specialised components (AI panel, RegRefSuggester) | M04/M05/M28 resources | Sanctions vendor (DJ/Refinitiv/LexisNexis or Accuity); NIBSS Watch-list | k6 perf harness; Pact CI gate; chaos drills baseline |
| 3 | weeks 25–36 | Audit-replay service hand-off | M06, M07, M08, M09 | M06 CDD, M07 alert workbench, M08 case workbench, M09 STR drafting | M06 (PII-aware), STR/CTR read-only | NIBSS BVN, NIMC NIN, CAC, FIRS TIN, NIS, FRSC, INEC PVC, NFIU goAML, AML Python sidecar | Sidecar deployments; goAML sandbox cut-over |
| 4 | weeks 37–44 | Performance hardening | M10, M21, M18 (full) | M18 dashboards | M10 + KPI widgets | CBN eFASS, FIRS TaxPro Max, NDIC, SEC, PenCom, NDPC, SCUML | k6 against staging; SLO sign-off iteration 1 |
| 5 | weeks 45–54 | RBAC recertification; PAM | M11, M13, M22, M24, M25 | M11 triage, M12 DPO console | M11/M13/M22/M24/M25 resources | LMS, voice/SMS intake (M13), EFCC ingress + freeze egress (M24) | Blue/green for screening; canary for AML |
| 6 | weeks 55–62 | OPcache + autoload tuning | M12, M14, M16 | M19 audit console | M12 RoPA/DPIA/DSR/Breach; M14; M16 | NDPC reporting portal; NCC SMS for consumer notices | NDPC platform-side RoPA evidence |
| 7 | weeks 63–72 | Final cross-cutting hardening | M15, M20, M23, M26, M27, M29, M30 | (regulator portal stub) | M15/M20/M23/M26/M27/M29/M30 resources | FATCA IDES; OECD CRS; SWIFT bridge; trading-system block API | k6 SLO sign-off iteration 2 |
| 8 | weeks 73–78 | Final sign-off | Final BRD-AC sweep; defect close-out | Phase-2 regulator portal | Final defect close-out | Final integration smoke | Pen-test; CBN cyber self-assessment; go-live readiness review |

Two consecutive monthly observation windows hitting every NFR are required before prod go-live (per TRD §17.5).

---

## 6. Status, Communication and Escalation

### 6.1 Daily

- 09:00 WAT — each agent updates `STATUS.md` (root): yesterday / today / blockers.
- 10:00 WAT — 15-minute stand-up (Slack thread or call): blockers only.

### 6.2 Weekly

- Friday 16:00 WAT — each agent posts `docs/weekly/YYYY-MM-DD.md` with: BRD modules advanced, AC IDs satisfied, AC IDs at risk, open `[NEEDS DECISION]`.

### 6.3 Decision Backlog

- `docs/decisions/open.md` is the single backlog for anything needing a human (CCO, CISO, DPO, Head Engineering). Format per item: title, context, options, recommendation, target decision date, decision owner.

### 6.4 Escalation Triggers — Recap

- `[NEEDS DECISION]` open >24h → escalate.
- NFR missed two consecutive runs → escalate.
- Security/privacy issue without BRD/TRD precedent → escalate.
- Regulator publishes new material instrument → Horizon scanner routes to triage; A2 + CCO consult.
- Constraint in §0.3 conflicting with reality → escalate.

---

## 7. Day-0 Kickoff Checklist

Before any agent starts coding, the following must be true:

1. ☐ All four canonical documents (BRD v2.0, TRD v1.1 Laravel, this prompt, Design System) are in the repo's `docs/` directory.
2. ☐ The Drafts XLSM is in the repo's `seeds/` directory (read-only).
3. ☐ The six agents have introduced themselves in `STATUS.md` and confirmed they have read their §4 section.
4. ☐ The cloud accounts (prod / staging / dev / dr) exist and are owned by A6.
5. ☐ Keycloak is deployed (or planned for Phase-0 Week 1) and federated to the bank IdP.
6. ☐ The decision backlog (`docs/decisions/open.md`) seeded with the ten OD-* items from TRD §21.2 (region pinning, HSM choice, PEP source, sanctions vendor, regulator portal scope, LMS, DSR intake, ESG pipeline, whistleblowing voice provider, multi-tenant deployment).
7. ☐ The DPO has signed off the platform-side RoPA template and the data-class register.
8. ☐ The CISO has signed off the threat model template.
9. ☐ The CCO has signed off the Day-1 obligation count (352) and the Day-1 sanctions count (417) — flag any reconciliation issues now.
10. ☐ Each agent has confirmed in writing that they accept the constraints in §0.3 and the Definition of Done in §0.4.

---

## 8. Final Words to All Six Agents

You are building a platform that one Tier-1 Nigerian bank may rely on to answer a CBN examination, to file a goAML STR before the 24-hour deadline, to keep a board out of legal exposure, and to protect customer data under the NDPA. Quality matters more than speed; honesty about progress matters more than appearing on schedule.

When you ship something, you own it through production. When you find an inconsistency between two of the canonical documents, raise it; don't paper over it. When you finish a deliverable, write the test that proves it works and the README that proves the next agent can find it.

You have everything you need to execute. Start with §0–§3, then your §4 section, then the codebase.

---

**End of Document**
