# TECHNICAL REQUIREMENTS DOCUMENT — LARAVEL VARIANT
## ATHERIS COMPLIANCE MANAGEMENT SOLUTION
### Technical Specification, Architecture and Build Reference for Nigerian and African Financial Institutions

| Field | Value |
|---|---|
| Product Code | ATHERIS-CMS-2.0 |
| Document Title | Technical Requirements Document — Laravel Variant — Atheris Compliance Management Solution |
| Version | 1.1 (Laravel variant of v1.0) |
| Status | Engineering Review Draft |
| Date | 14 May 2026 |
| Prepared by | Mohammed Ali |
| Classification | Internal — Confidential |
| Variant of | Atheris TRD v1.0 (Java 21 + Spring Boot variant), 14 May 2026 |
| Companion Document | Atheris BRD v2.0 (14 May 2026) — this TRD is binding only when read alongside the BRD |
| Stack Substitution Summary | Application runtime swaps from **Java 21 + Spring Boot 3** to **PHP 8.3 + Laravel 11 + Octane (RoadRunner)**, with **Filament 3** for back-office admin, **Inertia.js + React** for workbench UIs, and three **sidecar services** preserved from v1.0 architecture: a **Python FastAPI** AML rule + ML service, a **Temporal (PHP SDK)** long-running workflow worker, and an **optional Go service** for the highest-throughput screening hot path. PostgreSQL 16, OpenSearch, Redis, Kafka (MSK), S3 Object Lock, CloudHSM, Keycloak, Istio, Kong, the Nigeria-resident active-active topology, every NFR target, every data-model decision, every security control, every audit-trail requirement and the entire Day-1 migration plan are **preserved unchanged**. |

---

## 1. Document Control

### 1.1 Version History

| Version | Date | Author | Change Summary | Approver |
|---|---|---|---|---|
| 0.1 | 2026-05-13 | M. Ali | TRD outline (Java) | — |
| 1.0 | 2026-05-14 | M. Ali | First complete draft (Java + Spring Boot stack) | Pending Head Engineering, CISO, CCO, DPO, Internal Audit |
| **1.1 — Laravel Variant** | **2026-05-14** | **M. Ali** | **Stack-substituted variant: PHP 8.3 + Laravel 11 + Octane (RoadRunner) + Filament 3 + Inertia/React + Temporal (PHP) + Python FastAPI sidecar for AML rules. Architecture, NFRs, data model, security model, integrations, audit trail, retention, deployment topology, Day-1 migration plan and compliance posture of v1.0 are preserved.** | **Pending Head Engineering, CISO, CCO, DPO, Internal Audit, Implementation Partner Lead** |

### 1.2 Variant Scope — What Changes and What Doesn't

| Concern | v1.0 (Java/Spring) | v1.1 (Laravel) | Change |
|---|---|---|---|
| Application runtime | Java 21 + Spring Boot 3 | PHP 8.3 + Laravel 11 + Octane (RoadRunner) | **Swapped** |
| Back-office UI framework | React 18 + Vite (SPA, separate codebase) | Filament 3 (admin) + Inertia.js + React (workbench) | **Swapped** |
| Long-running workflow engine | Camunda 8 (Zeebe) | Temporal (PHP SDK + `laravel-workflow`) | **Swapped** |
| AML rule engine | Drools/KIE (JVM, in-process) | Python FastAPI sidecar consuming Kafka | **Same sidecar pattern, Drools removed** |
| Optional hot-path screening | Spring WebFlux | Octane mode + optional Go microservice | **Adjusted** |
| RDBMS | PostgreSQL 16 (Aurora) | PostgreSQL 16 (Aurora) | **Unchanged** |
| Search | OpenSearch 2.x | OpenSearch 2.x | **Unchanged** |
| Cache | Redis 7 | Redis 7 | **Unchanged** |
| Stream backbone | Kafka 3.x (MSK) | Kafka 3.x (MSK) | **Unchanged** |
| Object storage | S3 + Object Lock | S3 + Object Lock | **Unchanged** |
| HSM | CloudHSM (NG) | CloudHSM (NG) | **Unchanged** |
| Identity | Keycloak + SPIRE | Keycloak + SPIRE | **Unchanged** |
| Service mesh / API gateway | Istio + Kong | Istio + Kong | **Unchanged** |
| Cloud region & topology | Active-active across 2 AZs, NG-resident, DR | Active-active across 2 AZs, NG-resident, DR | **Unchanged** |
| NFR targets | Per BRD §9 | Per BRD §9 — same | **Unchanged** |
| Security controls | OWASP ASVS L2, ISO 27001, CBN Cyber, NDPA | Same | **Unchanged** |
| Audit trail (hash-chain + Merkle + S3 Object Lock) | Per v1.0 §10.19 | Same mechanism, Laravel-implemented | **Same mechanism** |
| Data model (entities, DDL) | Per v1.0 §7–8 | Same DDL; Eloquent models map onto it | **Unchanged** |
| Day-1 migration plan | Spring Batch loader | Laravel Excel + Horizon jobs | **Same plan, swapped tool** |
| Integration patterns (NIBSS, NIMC, CAC, NFIU goAML, etc.) | Java client libs | PHP client libs + Guzzle | **Same external contracts** |

### 1.3 Owners and Approvers

Identical to v1.0 §1.2. The Head of Engineering remains the document owner.

### 1.4 Conventions

Identical to v1.0 §1.3. Functional Requirements (`FR<m>.<n>`), Acceptance Criteria (`AC<m>.<n>`), NFRs (`NFR<n>`), Technical Requirements (`TR<n>` / `TR-M<m>.<n>`) and Architecture Decision Records (`ADR-<n>`) carry forward. ADRs whose decision changes are renumbered as `ADR-001a`, `ADR-009a`, etc. in §3.3.

---

## 2. Introduction and Scope

### 2.1 Purpose

This TRD variant translates the Atheris BRD v2.0 into a build specification using **PHP 8.3 + Laravel 11** as the primary application runtime, in response to a decision to standardise on PHP/Laravel for the bank's compliance platform. All architectural, security, data, integration and operational requirements of v1.0 are preserved; only the application-layer technology choices change, plus the few downstream consequences (queue framework, ORM, testing tooling, observability surfaces, build pipeline tools).

### 2.2 Why a Laravel Variant Is Sensible Here

| Concern | Outcome |
|---|---|
| Most modules are CRUD- and workflow-heavy back-office tools | Filament 3 + Laravel modules compress engineering effort meaningfully versus building those screens in React-only SPA |
| Talent supply in Lagos | PHP / Laravel talent pool is deeper and lower-cost than senior Spring Boot talent |
| Time-to-MVP | Laravel + Filament reaches a usable internal product faster on the CRUD-heavy 80% (Universe, Policies, Returns, Sanctions KB, Account Mgmt, Cash Mgmt, Conduct, Whistleblowing, Vendor, Training) |
| Performance hot paths | Octane (RoadRunner) brings PHP to within range of JVM throughput; AML and hot-path screening continue to use a Python or Go sidecar as in v1.0 |
| Maintainability | A modular monolith in Laravel is operationally simpler than ~36 Spring Boot microservices |
| Compliance of platform itself | Unchanged — same Postgres, S3 Object Lock, CloudHSM, NDPA controls |

### 2.3 In Scope (Technical)

As v1.0 §2.2: the cloud-native, Nigeria-resident, active-active platform that implements modules M01–M30; all NFRs; all integrations; all Day-1 data load from the canonical Drafts (43 regulators, 13 instrument types, 29 areas of focus, 352 instruments, 1,227+ CRMP rows, 236 monitoring activities, 187 returns, 417 sanctions).

### 2.4 Out of Scope (Technical)

As v1.0 §2.3.

### 2.5 Companion Documents

- Atheris BRD v2.0 (14 May 2026)
- Atheris TRD v1.0 (Java variant) — kept on file as alternate-stack reference
- Compliance Management Toolkits DRAFT (working file)

---

## 3. Architectural Principles and Constraints

### 3.1 Architectural Principles

Identical to v1.0 §3.1. The twelve principles — Nigeria-first, cloud-native container-first, service decomposition by bounded context, asynchronous-by-default for cross-context flows, configuration over code, maker–checker by design, immutable audit by default, stateless services / stateful stores, least privilege everywhere, polyglot persistence where justified, defence in depth, evidence-by-construction — carry forward unchanged.

Two principles are reinforced by this variant:

- **Modular monolith with selective sidecars.** Where v1.0 chose ~36 Spring Boot microservices, v1.1 chooses one Laravel modular monolith with strict module boundaries (separate DB schemas, separate queue connections, no cross-module Eloquent relationships) plus three small sidecars where PHP is the wrong tool (AML rule engine in Python, long-running workflows in Temporal, optional hot-path screening in Go). This trades operational simplicity for slightly more careful in-process discipline.
- **Octane-mode for hot paths.** Laravel's request-per-process default would not meet `NFR1` (screening p95 ≤500ms) and `NFR2` (onboarding p95 ≤3s) reliably at scale; the platform runs in Octane long-lived workers on RoadRunner for the screening, KYC orchestration, dashboard read paths and AML alert ingest. Back-office routes can run on PHP-FPM if Octane warm-up turns out to be operationally inconvenient for any given route — that's a per-route decision, not a stack-wide one.

### 3.2 Constraints

Identical to v1.0 §3.2. C1–C15 carry forward. The performance budgets are unchanged: real-time sanctions screening p95 ≤500ms (`C3`), onboarding round-trip p95 ≤3s (`C4`), AML alert latency p99 ≤30s (`C5`), search p95 ≤2s (`C6`), availability ≥99.9% business hours / ≥99.5% 24×7 (`C7`), RPO ≤15 minutes / RTO ≤4 hours (`C8`), audit immutability with ≥5-year retention (`C9`), maker-checker for every state-changing operation (`C10`), tipping-off prevention on STR contents (`C11`).

The Laravel variant adds two stack-specific constraints:

| ID | Constraint | Source |
|---|---|---|
| C16 | All hot-path HTTP services (screening, KYC orchestration, AML alert ingest, dashboard read paths) MUST run under Laravel Octane on RoadRunner; PHP-FPM is permitted only for back-office / Filament-served routes that are not on a performance-critical path | NFR1, NFR2, NFR3, NFR5 |
| C17 | Module boundaries enforced at code-review time AND at runtime — no cross-module Eloquent relationships; cross-module access via service classes or Kafka events only | v1.0 §4.4 data ownership rules carry forward |

### 3.3 Architecture Decision Records

Architecture Decision Records carry forward from v1.0 with the substitutions below. Where a v1.0 ADR is superseded, the superseding ADR is labelled with the same number suffixed `a` and the v1.0 ADR is marked Superseded.

| ID | Decision | Status |
|---|---|---|
| ADR-001 | ~~Primary stack is Java 21 + Spring Boot 3.x on Kubernetes~~ | Superseded by ADR-001a |
| **ADR-001a** | **Primary stack is PHP 8.3 + Laravel 11 LTS on Kubernetes, running under Octane (RoadRunner 2024.x) for HTTP services and Laravel Horizon for queues; PHP-FPM optionally for non-hot back-office routes** | **Accepted** |
| ADR-002 | Primary cloud is a Nigerian hyperscaler region active-active across two AZs, DR to a second NG region or on-shore Tier-III DC | Accepted, region selection still pending |
| ADR-003 | RDBMS = PostgreSQL 16; document/search = OpenSearch 2.x; analytics = ClickHouse; cache = Redis 7; stream = Kafka 3.x (MSK) | Accepted |
| ADR-004 | Object storage = S3-compatible with Object Lock; HSM = CloudHSM (NG) | Accepted |
| ADR-005 | Identity is OIDC / OAuth 2.1 via Keycloak federated to bank Azure AD / Okta; service-to-service is SPIFFE/SPIRE workload identities + mTLS | Accepted |
| ADR-006 | Service mesh is Istio | Accepted |
| ADR-007 | API gateway is Kong (north-south); service mesh handles east-west | Accepted |
| ADR-008 | ~~Frontend is React 18 + TypeScript + Vite SPA~~ | Superseded by ADR-008a |
| **ADR-008a** | **Frontend is Filament 3 for the bulk of back-office CRUD (M01–M03, M10, M14, M15, M17, M22, M24–M30, M28) AND Inertia.js + React + TypeScript for the workbench screens (M07 alert workbench, M08 case management, M09 STR drafting, M11 complaints triage, M12 DPO console, M18 dashboards). Tailwind CSS for both. Material-UI is replaced by Filament's design system (admin) and a custom Tailwind component library (workbench).** | **Accepted** |
| ADR-009 | ~~Workflow engine is Camunda 8 (Zeebe)~~ | Superseded by ADR-009a |
| **ADR-009a** | **Long-running orchestration is Temporal.io (community edition self-hosted, or Temporal Cloud if approved). Workflows authored in PHP via `temporal/sdk` and integrated into Laravel via the `laravel-workflow` package. State machines for short workflows (e.g., policy approval, control test outcome) use `spatie/laravel-model-states` directly in Laravel without Temporal.** | **Accepted** |
| ADR-010 | ~~AML rule engine is Drools (KIE) inside the Java service~~ | Superseded by ADR-010a |
| **ADR-010a** | **AML rule engine and behavioural ML run as a Python 3.12 + FastAPI sidecar service, consuming the same Kafka `txn.ingested` topic that Laravel emits to. Rules are authored in a Python-native DSL (built on `business-rules` or a custom DSL backed by `lark`). The PHP code in Laravel produces transactions but does not evaluate AML rules.** | **Accepted** |
| ADR-011 | Audit-event ledger is a hash-chained append-only Postgres table, sealed daily as a Merkle root to S3 Object Lock; no blockchain | Accepted |
| ADR-012 | The platform is multi-tenant-ready; Phase-1 single-tenant | Accepted |
| ADR-013 | Document AI for PDF extraction and OCR is AWS Textract with Tesseract fallback | Accepted |
| ADR-014 | ~~Compliance Monitoring scheduler is Spring Batch + Quartz; long-running flows in Camunda~~ | Superseded by ADR-014a |
| **ADR-014a** | **Compliance Monitoring scheduler is Laravel Scheduler (Cron) + Horizon-managed queue jobs for fan-out. Long-running flows run in Temporal. Bulk imports (Day-1 load, ad-hoc bulk obligation imports) use `maatwebsite/excel` + chunked queue jobs.** | **Accepted** |
| ADR-015 | Streaming analytics and CCM queries against connected systems use a dedicated read replica per source plus Kafka Connect | Accepted |
| **ADR-016** | **For the highest-throughput hot path (real-time sanctions screening) the platform deploys an `octane-screening` Laravel service; if k6 in Phase-0 cannot meet the 500ms p95 budget under projected real load, the platform falls back to a small Go microservice (`go-screening`) that exposes the same JSON contract. The decision is empirical and made at the close of Phase 0.** | **Accepted (conditional)** |
| **ADR-017** | **Module boundaries inside the Laravel monolith are enforced with `nwidart/laravel-modules` (or `mizanto/laravel-modular`). Each Bounded Context from v1.0 §4.2 becomes one module with its own DB schema, its own queue connection, its own service classes, and its own routes file. Cross-module DB joins are forbidden at code-review time; ADR-017 violators are flagged by a custom static-analysis rule in PHPStan.** | **Accepted** |
| **ADR-018** | **PII field-level encryption uses Laravel's `Encryptable` Eloquent casts backed by AWS KMS DEKs wrapped in a CloudHSM CMK; for fields where Postgres-side query support is needed (e.g., partial deterministic lookup on identifier values), Postgres `pgcrypto` with HSM-wrapped DEKs is used at the database layer with a thin Eloquent custom cast.** | **Accepted** |

---

## 4. Logical Architecture

### 4.1 Macro View

The platform composes a Laravel modular monolith hosting most of the BRD modules, three sidecar services where PHP is the wrong tool, the same shared cross-cutting concerns from v1.0 (Identity, Audit & Evidence Vault, Workflow, Observability) and the same event backbone.

```
                       ┌────────────────────────────────────────────────────┐
                       │  Regulator-Portal (RO, Phase-2) / Partner Gateway  │
                       └─────────────────────────▲──────────────────────────┘
                                                 │
                                          ┌──────┴───────┐
                                          │  Kong API GW │ (north-south)
                                          └──────▲───────┘
                                                 │
   ┌─────────────────────────────────────────────┴──────────────────────────────┐
   │                  ATHERIS MODULAR MONOLITH (Laravel 11 + Octane)            │
   │                                                                            │
   │  Modules (per BRD bounded contexts — one Laravel module each):             │
   │   library  │  crmp     │  returns │  fincrime  │  customer  │  conduct    │
   │   policy   │  controls │  calendar│  screening │  kyc       │  whistle    │
   │   gov      │  dpo      │  incident│  tax       │  fatca     │  esg        │
   │   capmkt   │  openbank │  account │  cash      │  sanctkb   │  training   │
   │   vendor   │  dashboard│  audit   │  evidence  │  identity  │  notify     │
   │                                                                            │
   │  Runtime:                                                                  │
   │   - HTTP hot paths    → Octane (RoadRunner)                                │
   │   - Back-office HTTP  → Octane (RoadRunner) or PHP-FPM                     │
   │   - Queue workers     → Laravel Horizon (Redis-backed)                     │
   │   - Cron              → Laravel Scheduler                                  │
   │   - Admin UI          → Filament 3 (CRUD modules)                          │
   │   - Workbench UI      → Inertia.js + React + Tailwind                      │
   └────────────────────────────────────────────────────────────────────────────┘
                          ▲                ▲                ▲
                          │                │                │
   ┌──────────────────────┴────┐   ┌──────┴──────────┐   ┌─┴───────────────────┐
   │  aml-rules (Python /      │   │ workflow-worker │   │  go-screening       │
   │  FastAPI) — Drools-       │   │ (Temporal PHP   │   │  (optional, only if │
   │  replacement rule engine, │   │  SDK worker)    │   │  Octane misses p95) │
   │  consumes txn.ingested    │   │ runs Temporal   │   │                     │
   │                           │   │ workflows       │   │                     │
   └───────────────────────────┘   └─────────────────┘   └─────────────────────┘
                          │                │                │
                  ┌───────┴────────────────┴────────────────┴───────┐
                  │             Kafka Event Backbone (MSK)          │
                  └────────────────────────┬────────────────────────┘
                                           │
   ┌────────────┬────────────┬─────────────┴──────┬────────────┬────────────────┐
   │ Identity   │ Audit &    │ Postgres (Aurora)  │ OpenSearch │  Observability │
   │ (Keycloak  │ Evidence   │ + Redis + S3       │            │  (OTel, Loki,  │
   │  + SPIRE)  │ Vault      │   Object Lock      │            │  Prom, Tempo,  │
   │            │ (M19)      │                    │            │  Laravel Pulse)│
   └────────────┴────────────┴────────────────────┴────────────┴────────────────┘
```

### 4.2 Bounded Contexts → Laravel Modules

The fifteen bounded contexts from v1.0 §4.2 carry forward as Laravel modules. Each module:

- Lives under `modules/<Name>/` and is registered with `nwidart/laravel-modules` per `ADR-017`.
- Owns one or more Postgres schemas (e.g., `library`, `crmp`, `fincrime`); migrations live under the module.
- Owns one or more Kafka topics; the producer/consumer classes live under the module.
- Exposes its public API as a thin set of service classes (interfaces) in `modules/<Name>/Services/` — these are the only legal entry points from other modules.
- Has its own Filament panel under `modules/<Name>/Filament/` and/or Inertia pages under `modules/<Name>/Pages/`.
- Has its own queue connection name and Horizon supervisor (so noisy neighbours can be throttled independently).

| BC ID | Module Name (Laravel) | BRD Modules | Schemas Owned (Postgres) | Filament Panel? | Inertia Workbench? |
|---|---|---|---|---|---|
| BC1 | `library` | M01, M03 | `library`, `obligations` | Yes | — |
| BC2 | `policy` | M02 | `policy` | Yes | — |
| BC3 | `crmp` | M04 | `crmp` | Yes | — |
| BC3 | `controls` | M05 | `controls` | Yes | — |
| BC4 | `returns` | M10, M17 | `returns`, `calendar` | Yes | — |
| BC5 | `screening` | M07 | `screening` | — | Yes (alert workbench) |
| BC5 | `aml` (proxy to aml-rules sidecar) | M08 | `aml` (alerts + cases) | — | Yes (case workbench) |
| BC5 | `nfiu` | M09 | `nfiu` | — | Yes (STR/CTR workbench) |
| BC6 | `customer` | M06 | `customer` | Yes (admin only — no PII display by default) | Yes (CDD/EDD workbench) |
| BC7 | `complaints` | M11 | `complaints` | Yes | Yes (consumer-protection triage) |
| BC7 | `whistle` | M13 | `whistle` (restricted role) | Yes (Ethics Officer only) | — |
| BC7 | `conduct` | M22 | `conduct` | Yes | — |
| BC7 | `abac` | M24 | `abac` | Yes | — |
| BC7 | `gov` | M25 | `gov` | Yes | — |
| BC8 | `dpo` | M12, M16 (data) | `dpo`, `incident` | Yes | Yes (DPO console) |
| BC9 | `tax` | M21 | `tax` | Yes | — |
| BC9 | `fatca` | M20 | `fatca` | Yes | — |
| BC10 | `esg` | M26 | `esg` | Yes | — |
| BC11 | `capmkt` | M27 | `capmkt` | Yes | — |
| BC11 | `openbank` | M23 | `openbank` | Yes | — |
| BC12 | `account` | M29 | `account` | Yes | — |
| BC12 | `cash` | M30 | `cash` | Yes | — |
| BC13 | `sanctkb` | M28 | `sanctkb` | Yes | — |
| BC14 | `training` | M15 | `training` | Yes | — |
| BC15 | `vendor` | M14 | `vendor` | Yes | — |
| Cross | `dashboard` | M18 | (read-only views) | — | Yes (CCO/Exec/Board dashboards) |
| Cross | `audit` | M19 | `audit` (no-update role) | — | — (Internal Audit views via Inertia in M19 console) |
| Cross | `evidence` | M19 (vault) | `evidence` | — | — |
| Cross | `identity` | §14 RBAC | `identity` | Yes (Super Admin only) | — |
| Cross | `notify` | (notifications) | `notify` | — | — |

### 4.3 Service Inventory (Reduced from v1.0)

Where v1.0 listed ~36 Spring Boot services, v1.1 collapses them into the components below.

| Component | Type | Responsibility |
|---|---|---|
| `atheris-app` | Laravel monolith (Octane + Horizon + Filament + Inertia) | All 30 BRD modules' business logic, HTTP, queues, scheduler, admin UI, workbench UI |
| `atheris-octane-screening` | Same Laravel codebase deployed as a screening-specialised Octane pool | Hot-path real-time screening endpoint; scaled and tuned independently |
| `atheris-aml-rules` | Python 3.12 + FastAPI + (optional) PyFlink for streaming | AML rule evaluation + behavioural ML; replaces Drools (`ADR-010a`) |
| `atheris-workflow-worker` | PHP 8.3 + Temporal SDK worker | Long-running workflows (CRMP review cycle, breach 72h timer, fit-and-proper, returns approval matrix, etc.) — `ADR-009a` |
| `atheris-audit-writer` | Laravel codebase, separate deployment with restricted DB role | Append-only writer to `audit.event`; Merkle-root sealer |
| `atheris-go-screening` | (Optional) Go 1.22 + Fiber/Echo | Fallback hot-path screening if Octane cannot hit p95 ≤500ms — `ADR-016` |
| `atheris-web` | Static React build served via CDN+Kong for Phase-2 regulator portal | Read-only regulator portal |

Total deployable artefacts on Day 1: **five** (`atheris-app`, `atheris-octane-screening`, `atheris-aml-rules`, `atheris-workflow-worker`, `atheris-audit-writer`), plus the optional sixth if performance requires.

### 4.4 Data Ownership Rules

Identical to v1.0 §4.4 with `ADR-017` adding compile-time enforcement: cross-module Eloquent relationships are forbidden; a custom PHPStan rule (`Atheris\PHPStan\NoCrossModuleEloquent`) fails the build if it detects `App\Modules\<A>\Models\*` being imported from `App\Modules\<B>\*` outside of `App\Modules\<B>\Services\<A>Client.php` adapter classes. Cross-module access is via service classes (synchronous) or Kafka events (asynchronous).

### 4.5 Process Topology

| Process | Image | Replicas (Day 1) | Mode |
|---|---|---|---|
| `atheris-app-web` | `atheris-app` | 6 across 2 AZs | Octane / RoadRunner — back-office + workbench routes |
| `atheris-octane-screening` | `atheris-app` (screening profile) | 8 across 2 AZs | Octane / RoadRunner — screening + KYC orchestration routes only |
| `atheris-app-horizon` | `atheris-app` | 4 across 2 AZs | Horizon supervisors for queue work (5 supervisors: `default`, `library`, `aml-bridge`, `returns`, `audit`) |
| `atheris-app-scheduler` | `atheris-app` | 1 (leader-elected via Redis lock) | Laravel Scheduler — cron orchestration |
| `atheris-aml-rules` | `atheris-aml-rules` (Python) | 6 across 2 AZs | FastAPI + Kafka consumer |
| `atheris-workflow-worker` | `atheris-workflow-worker` (PHP Temporal worker) | 3 across 2 AZs | Temporal task pollers |
| `atheris-audit-writer` | `atheris-audit-writer` (Laravel) | 3 across 2 AZs | Synchronous audit append + nightly Merkle sealer |
| `atheris-go-screening` | (optional) | 4 across 2 AZs | Conditional per `ADR-016` |

---

## 5. Physical / Deployment Architecture

### 5.1 Region Strategy

**Unchanged from v1.0 §5.1.** AWS af-south-1 (with NG local zone when GA) primary; warm DR in a second NG region or on-shore Tier-III DC; CloudHSM in Nigerian region; S3 with Object Lock and cross-region replication into NDPA-bound bucket.

### 5.2 Topology

**Unchanged from v1.0 §5.2.** Two AZs active-active for compute; Aurora multi-AZ with synchronous replica; MSK across 3 AZs; OpenSearch master+data across 3 AZs; CloudHSM cluster in primary region; S3 Evidence + Universe buckets with Object Lock.

### 5.3 Network

**Unchanged from v1.0 §5.3.** 3-tier VPC (Public, App, Data), NAT egress with FQDN allowlist, mTLS via Istio for east-west and Kong+ALB for north-south, dedicated VPN/Direct Connect for SWIFT and NIBSS, CIDR plan 10.20.0.0/16 (prod) and 10.21.0.0/16 (DR).

### 5.4 Capacity (Initial Sizing — Laravel-tuned)

The Laravel runtime sizing differs slightly from the Java sizing in v1.0 because Octane worker pools are sized by worker-count × concurrency-per-worker, not just by JVM heap.

| Tier | Resource | Day-1 Sizing | Notes |
|---|---|---|---|
| EKS node group — app | m6i.2xlarge × 12 across 2 AZs | 96 vCPU / 384 GiB | Hosts `atheris-app-web` (Octane), `atheris-octane-screening`, `atheris-app-horizon`, `atheris-audit-writer`, `atheris-workflow-worker` |
| EKS node group — aml | c6i.4xlarge × 4 across 2 AZs | 64 vCPU / 128 GiB | Hosts `atheris-aml-rules` (CPU-bound rule eval + ML) |
| Aurora | db.r6g.4xlarge writer + 2 readers | 16 vCPU / 128 GiB | Same as v1.0; reader auto-scaling up to 10 |
| MSK | 3 × kafka.m5.2xlarge | 60 MB/s sustained | Same as v1.0 |
| OpenSearch | 3 master m6g.large + 6 data r6g.xlarge | 150 GB hot, 1 TB warm | Same as v1.0 |
| Redis | cache.r7g.large × 3 nodes | 13 GB memory | Cluster mode encrypted; serves Octane caches + queues |
| HSM | 2 × hsm1.medium (CloudHSM) | — | Same as v1.0 |
| Temporal cluster | 3 nodes on EKS | — | Persistent volumes; Cassandra/Postgres backend (Postgres re-used) |

**Octane worker tuning (RoadRunner) — Day-1 baseline:**

| Service | Workers per pod | Concurrency per worker | Total per pod | Heap target |
|---|---|---|---|---|
| `atheris-app-web` | 16 | 1 | 16 RPS-equivalent | 256 MB per worker |
| `atheris-octane-screening` | 32 | 1 | 32 RPS-equivalent | 192 MB per worker |
| Horizon | n/a (worker per supervisor) | — | per Horizon config | 256 MB per worker |

[ASSUMPTION] These are starting figures; Phase-0 capacity testing (§17.4) will re-tune them per the actual hardware and concurrency observed.

---

## 6. Technology Stack

### 6.1 Bill of Materials (Laravel Variant)

| Layer | Choice | Version | Rationale |
|---|---|---|---|
| Language | PHP | 8.3 | Modern PHP with native enums, readonly classes, first-class callables, async via Fibers; Laravel 11 LTS minimum |
| Application framework | Laravel | 11 LTS | Bank-grade ecosystem; strong typing via stubs; mature queue, scheduler, validation, routing |
| Long-lived runtime | Laravel Octane on **RoadRunner** | Octane 2.x, RR 2024.x | Stable, Go-based, enterprise-proven runner; supports HTTP/2; alternative FrankenPHP for Phase-2 if matured |
| Per-route fallback runtime | PHP-FPM 8.3 | — | Optional for non-hot back-office routes if Octane warm-up is operationally inconvenient |
| Build / dependency | Composer | 2.7+ | — |
| Containers | Distroless / Chainguard PHP images, hardened by bank's image-baking pipeline | latest LTS | Smaller attack surface |
| Orchestration | Kubernetes (EKS) | 1.30+ | Same as v1.0 |
| Service mesh | Istio | 1.22+ | mTLS, authz, traffic |
| API gateway | Kong Gateway | 3.7+ | Partner / regulator-portal APIs |
| Admin UI | Filament | 3.x | Admin panels for CRUD-heavy modules; native Tailwind; built-in tables, forms, actions, exports, widgets |
| Workbench UI | Inertia.js + React + TypeScript + Vite | Inertia 2.x / React 18 / Vite 5 | For sophisticated screens (alert workbench, STR drafting, DPO console, dashboards) — same React skill set as v1.0 but server-driven routing |
| Component library | Tailwind UI + headless UI + custom Atheris design tokens | latest | Accessible (WCAG AA); shared with Filament |
| Charts (dashboards) | ECharts (via React wrapper) | 5.x | Same as v1.0 |
| ORM | Eloquent | 11.x | With `Encryptable` casts (`ADR-018`) and module-scoped models |
| Migrations | Laravel migrations (with `doctrine/dbal` for advanced Postgres features); critical schema changes via raw SQL in migration files | — | Same DDL as v1.0 §8 |
| Validation | Laravel form requests + `spatie/laravel-data` + custom validators (BVN, NIN, TIN, RC number, BN number) | 4.x | — |
| RBAC | `spatie/laravel-permission` | 6.x | Implements BRD §14.3 matrix; integrates with Keycloak roles via Socialite groups |
| State machines | `spatie/laravel-model-states` | 2.x | Short-lived state workflows (policy approval, control test, complaint) |
| Long-running workflows | Temporal PHP SDK + `laravel-workflow` | latest | `ADR-009a` — replaces Camunda 8 |
| Activity log (legacy compat only) | `spatie/laravel-activitylog` | 4.x | NOT the audit trail (`audit.event` hash-chain is authoritative); used only for non-compliance logging where convenient |
| Excel/CSV | `maatwebsite/excel` | 3.x | Day-1 import; ad-hoc bulk imports per `ADR-014a` |
| PDF generation | `barryvdh/laravel-dompdf` + LibreOffice headless for high-fidelity policy renders | — | Watermarked policy PDFs, regulator-pack PDFs |
| Queue | Laravel Horizon (Redis-backed) | 5.x | Per `ADR-014a` |
| Cache | Laravel Cache → Redis | — | — |
| Search | Laravel Scout + `babenkoivan/elastic-scout-driver` (OpenSearch) | latest | Universe search and CRMP search |
| Kafka | `mateusjunges/laravel-kafka` (uses `php-rdkafka` extension) | latest | Same `kafka` topology as v1.0 |
| HTTP client | Laravel HTTP (Guzzle) + Saloon for SDK-style integrations | latest | NIBSS, NIMC, CAC, FIRS, NIS, NFIU goAML, vendor APIs |
| Async parallel HTTP | Guzzle promises + Laravel Octane concurrency primitives | — | KYC orchestration fan-out within 3s p95 |
| Real-time push (dashboard updates, alert workbench live counts) | Laravel Reverb (or Pusher) | latest | WebSockets for live UI counts |
| Identity (humans) | Laravel Socialite (OIDC) → Keycloak | latest | OIDC code flow + PKCE |
| Identity (services) | SPIFFE/SPIRE + mTLS via Istio | 1.10+ | Same as v1.0 |
| API tokens | Laravel Sanctum (SPA) + Passport (OAuth 2.1 client-credentials for partner/regulator-portal) | latest | — |
| Secrets | HashiCorp Vault (Laravel `vault-php` client) | 1.16+ | Dynamic DB creds; transit |
| HSM | AWS CloudHSM | — | Same as v1.0; key ops via AWS SDK PHP + HSM-resident CMK |
| Field-level encryption | Laravel `Encryptable` casts → AWS KMS DEK envelope; `pgcrypto` for query-needed fields | — | Per `ADR-018` |
| Doc AI / OCR | AWS Textract via AWS SDK PHP; Tesseract fallback via PHP-FFI or queued shell job | — | Same `library` module behaviour |
| Mail / notifications | Laravel Mail; AWS SES or bank SMTP relay; SMS via NCC-compliant SMS gateway | — | — |
| Frontend testing | Playwright + Pest browser plugin | — | E2E |
| PHP testing | Pest 3 (with PHPUnit underneath) | 3.x | Unit + integration + feature; preferred for new code |
| Static analysis | PHPStan / Larastan at level 9; Psalm in CI as a second opinion | latest | Strict typing; Laravel rule packs |
| Code style | Laravel Pint (PSR-12 + Laravel preset) + Rector for upgrades | latest | — |
| Mutation testing | Infection PHP | latest | For critical financial-crime modules |
| Performance testing | k6 | latest | Same as v1.0 |
| Mocking external services | WireMock (via Docker), `saloon` mock client for outbound HTTP | latest | Testcontainers-equivalent for PHP: `testcontainers/testcontainers-php` |
| Observability — APM/metrics | Laravel Pulse + OpenTelemetry PHP SDK + Prometheus (via OTel) | latest | Pulse for first-party insight; OTel for trace export |
| Observability — logs | Monolog → Loki (JSON, with PII-redaction processor) | — | Same retention as v1.0 (90d hot + 5y cold WORM) |
| Observability — traces | OpenTelemetry PHP SDK + Tempo | 1.x | W3C Trace Context propagated |
| Dashboards (ops) | Grafana | 11.x | Same as v1.0 |
| Local-dev debug | Laravel Telescope (non-prod only; disabled in prod) | latest | — |
| AML sidecar | Python 3.12 + FastAPI + Pydantic v2 + Uvicorn + Kafka-Python | — | Same as v1.0 |
| Workflow sidecar | Temporal Server + Temporal PHP SDK | latest | `ADR-009a` |
| Optional hot-path service | Go 1.22 + Fiber/Echo + segmentio/kafka-go | — | Conditional per `ADR-016` |
| CI/CD | GitHub Actions / GitLab CI + ArgoCD | latest | Same as v1.0; pipeline tools swap for PHP equivalents |
| IaC | Terraform + Helm | 1.7+ / 3.x | Same as v1.0 |
| Container registry | ECR with image scanning | — | OS + PHP-extension CVE gating |
| SBOM | Syft + Grype + Sigstore Cosign | latest | Same as v1.0 |
| Linting / security | Sonar (PHP plugin), Snyk for Composer + npm, OWASP Dependency-Check (Composer support), Trivy | latest | SAST/DAST/SCA |

### 6.2 Forbidden / Restricted Technologies

Beyond v1.0 §6.2 prohibitions (no Log4j 1.x, etc.), the Laravel variant prohibits:

- No PHP < 8.3 in any path.
- No Laravel < 11 LTS.
- No `eval()`, `unserialize()` on untrusted input, `extract()` of request data, `assert()` with strings, `mb_ereg_replace` with the `e` modifier.
- No use of `localStorage` for tokens in the Inertia React layer (cookie-secured, sameSite=Strict, HttpOnly).
- No `ddl-auto`-style auto-migrations in prod (Laravel doesn't expose this by default, but `php artisan migrate --force` is gated behind a release-pipeline approval).
- No long-running database transactions across Octane worker requests (Octane state leaks must be defended against by the `octane:check` PHPStan rules and explicit `DB::disconnect()` patterns where required).
- No `Cache::lock()` without an explicit owner and timeout.
- No copying of customer PII into developer laptops or non-prod environments without DPO sign-off and pseudonymisation (NFR19).

### 6.3 Laravel Module Conventions

- One module per bounded context per `ADR-017`.
- Each module exposes a `ServiceProvider`, a `Routes/api.php`, a `Routes/web.php` (for Filament), a `Database/migrations/`, a `Models/`, a `Services/`, a `Http/Controllers/`, a `Jobs/`, a `Events/`, a `Listeners/`, a `Filament/` and (optionally) a `Pages/` for Inertia views.
- Every controller returns a typed response object (`spatie/laravel-data` `Data` class) or a Laravel `JsonResponse` with an HTTP status + Problem Details body for errors.
- Every job and listener is `ShouldQueue` and idempotent (deduplication keys passed via `Cache::lock()` on the aggregate ID).
- Every long-running orchestration is a Temporal workflow, NOT a Laravel job chain.

### 6.4 Octane Discipline (Critical)

Because Octane reuses the application instance across requests, state leaks are the single biggest source of PHP-Octane bugs. The platform enforces:

- No use of static caches that hold per-request state; the `octane:check` linter catches common leaks (singletons depending on the `Request`, container singletons resolved at boot with request scope, etc.).
- All container-resolved services that depend on the request scope MUST be bound as `scoped()` (Laravel 11's scoped singleton lifecycle, which resets between requests).
- Database connections are released between requests via the `octane.warm` config; long-lived connections to Postgres are pooled via PgBouncer.
- The codebase forbids the use of `app()->instance()` outside `register()` of service providers.
- Pre-warming: the `OctaneWarmupListener` pre-resolves the most-used services (`screening`, `kyc`, `audit-writer`) at worker start to avoid first-request latency.

---

## 7. Data Architecture and Storage

### 7.1 Polyglot Persistence

**Unchanged from v1.0 §7.1.** The data stores, their purposes and the rationale carry forward:

- PostgreSQL 16 (Aurora) — transactional master data.
- OpenSearch 2.x — Universe full-text + CRMP search + alert workbench search.
- Redis 7 — hot reference data, sessions, rate-limit counters, Horizon queue backing, Octane cache.
- Kafka 3.x — event backbone.
- ClickHouse — analytics warehouse for dashboards.
- S3 with Object Lock (Compliance mode) — evidence vault, regulator PDFs, sealed audit packages.
- CloudHSM / Vault Transit — encryption keys, signing keys, mTLS CA.

The Laravel-specific notes are:

- Eloquent connects to Postgres via PgBouncer for connection pooling so Octane workers don't open one connection each. The connection pool is sized to the worker count.
- Laravel Cache and Session both back to Redis cluster mode; cache key prefixes per module to prevent cross-module collision.
- Laravel Scout indexes flow through the OpenSearch driver (`babenkoivan/elastic-scout-driver`); per-model index settings include n-gram analyser (size 3) and a custom phonetic analyser for Yoruba/Hausa/Igbo names.
- Kafka via `mateusjunges/laravel-kafka` configures one consumer group per Horizon supervisor.

### 7.2 Multi-Tenancy and Partitioning

**Unchanged from v1.0 §7.2.** Every table carries `tenant_id`; high-volume tables (`txn`, `alert`, `audit_event`) are range-partitioned by month and list-partitioned by tenant. Laravel Eloquent applies a global scope (`TenantScope`) on every model so tenant isolation is enforced at the query layer in addition to the database layer.

### 7.3 Logical Data Model

**Unchanged from v1.0 §7.3.** All tables and relationships are identical. Eloquent models map onto these tables with no schema-level differences from the Java variant.

The Eloquent model patterns are:

- One model per major table; `Model` classes live under `modules/<Name>/Models/`.
- Read-only models (for cross-module reads via service classes) extend a `ReadOnlyModel` base that throws on `save()`.
- PII-bearing models use `Encryptable` casts (`ADR-018`) to keep field-level encryption transparent to controllers and Filament resources.
- Audit-bearing models implement the `EmitsAuditEvent` trait, which hooks `saved`/`deleting`/`forceDeleted` model events and writes to the `audit-writer` queue.

### 7.4 Retention and Legal Hold

**Unchanged from v1.0 §7.4.** All retention classes carry the same minima. The nightly `retention-broker` is implemented as a Laravel scheduled job (`php artisan atheris:retention-evaluate`) that emits disposal candidates to a DPO approval workflow (Temporal).

### 7.5 Reference Data Distribution

**Unchanged from v1.0 §7.5.** BC1 (`library` module) owns the six reference taxonomies and the 12 CRMP themes. Updates publish `reference.updated` to Kafka; consumers materialise local read models. The Day-1 bootstrap seeder is now a Laravel `Seeder` class invoked by the `day1-loader` (§19).

### 7.6 Encryption

**Unchanged from v1.0 §7.6.** TLS 1.3 internal, AES-256-GCM for at-rest, field-level for PII. Laravel-specific implementation:

- Aurora storage encryption with HSM-resident CMK — unchanged.
- S3 SSE-KMS — unchanged.
- Field-level: `Encryptable` Eloquent cast generates a per-field DEK via AWS KMS GenerateDataKey, encrypts payload with `openssl_encrypt('aes-256-gcm', ...)`, stores the wrapped DEK + ciphertext + IV + tag in a Postgres `bytea` column; decrypt is per-call and audited via the `EncryptionAccess` middleware.
- `pgcrypto` is used at the database layer for fields that need partial deterministic lookup (e.g., `customer_identifier.value` for BVN/NIN exact match search).

---

## 8. Data Model Details and Sample DDL

**The DDL is unchanged from v1.0 §8.** The `instrument`, `audit_event`, `crmp_row`, `monitoring_activity`, `sanction_line`, `return_definition`, `return_run` and all other tables carry forward verbatim. The only difference is the way Laravel applies migrations: each Laravel module owns its `Database/migrations/` directory and runs via `php artisan migrate --path=modules/<Name>/Database/migrations`. Cross-module foreign keys are explicit and `restrict`-on-delete by default.

### 8.1 Eloquent Model Sketches (Representative)

**`Instrument` model (BC1 / library module)**

```php
<?php
namespace App\Modules\Library\Models;

use App\Casts\Encryptable;
use App\Concerns\BelongsToTenant;
use App\Concerns\EmitsAuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

final class Instrument extends Model
{
    use BelongsToTenant, EmitsAuditEvent;

    protected $table = 'library.instrument';
    protected $fillable = [
        'source_title', 'objectives', 'date_issue', 'date_commence', 'date_repeal',
        'regulator_id', 'instrument_type_id', 'nature_id', 'status_id',
        'area_of_focus_id', 'sanctions_summary', 'applicability', 'comment_on_status',
        'link_url', 'risk_rating_id', 'risk_rating_explain',
        'commercial_bank_relevance', 'commercial_bank_compliance_context',
        'parent_id', 'full_text_uri', 'ocr_indexed_at',
    ];
    protected $casts = [
        'date_issue' => 'date',
        'date_commence' => 'date',
        'date_repeal' => 'date',
        'ocr_indexed_at' => 'datetime',
    ];

    public function regulator(): BelongsTo
    {
        return $this->belongsTo(Regulator::class);
    }

    public function obligations()
    {
        return $this->hasMany(Obligation::class);
    }

    // Scout / OpenSearch indexable fields
    public function toSearchableArray(): array
    {
        return [
            'source_title' => $this->source_title,
            'objectives' => $this->objectives,
            'regulator' => $this->regulator->code ?? null,
            'type' => $this->instrumentType->name ?? null,
            'area' => $this->areaOfFocus->name ?? null,
            'risk' => $this->riskRating->name ?? null,
            'status' => $this->status->name ?? null,
        ];
    }
}
```

**`Customer` model (BC6 / customer module — PII field-level encryption)**

```php
<?php
namespace App\Modules\Customer\Models;

use App\Casts\Encryptable;
use Illuminate\Database\Eloquent\Model;

final class Customer extends Model
{
    use BelongsToTenant, EmitsAuditEvent;

    protected $table = 'customer.customer';
    protected $casts = [
        'full_name' => Encryptable::class,
        'dob' => Encryptable::class,
        'kyc_tier' => 'integer',
        'risk_score' => 'integer',
        'onboarded_at' => 'datetime',
        'dormant_at' => 'datetime',
    ];

    // Hidden by default; explicit ->makeVisible(['full_name','dob']) required, which triggers EncryptionAccess audit.
    protected $hidden = ['full_name', 'dob'];

    public function identifiers()
    {
        return $this->hasMany(CustomerIdentifier::class);
    }

    public function psc()
    {
        return $this->hasMany(Psc::class);
    }
}
```

### 8.2 Indexing Strategy

**Unchanged from v1.0 §8.3.** All indexes carry forward. Laravel migrations declare them; partial indexes (e.g., on `customer.status='active'`) are written as raw SQL inside Laravel migration `up()` methods.

### 8.3 Data Quality and Validation

**Unchanged from v1.0 §8.4.** Laravel implementation:

- Schema-level CHECK constraints declared in raw SQL within migrations.
- Application-level: form requests for synchronous API validation, `spatie/laravel-data` `Data` classes for typed DTOs, and custom validators (e.g., `BvnRule`, `NinRule`, `TinRule`, `RcNumberRule`) for Nigerian identifier formats.
- Nightly `data-quality` job is a scheduled Laravel command (`php artisan atheris:dq:run`) emitting `dq.*` Kafka events.

---

## 9. API Architecture

### 9.1 API Styles

**Unchanged from v1.0 §9.1.** REST/JSON for north-south via Kong; REST/JSON or gRPC for east-west via Istio; Kafka events for async cross-BC; XML/SFTP for legacy regulator portals. The Laravel implementation uses:

- Laravel API routes (`routes/api.php` per module) for REST endpoints.
- `spatie/laravel-data` for request/response DTOs.
- Sanctum for SPA token auth; Passport for OAuth 2.1 client-credentials on external/partner APIs.
- Kafka via `mateusjunges/laravel-kafka` producers/consumers per module.

### 9.2 Versioning, Compatibility and Deprecation

**Unchanged from v1.0 §9.2.** URI versioning (`/v1/...`); backwards compatibility ≥18 months; 90-day deprecation notice.

### 9.3 Auth and Authorization

**Unchanged from v1.0 §9.3** in posture; Laravel-specific implementation:

- Humans: OIDC code-flow via Socialite → Keycloak; Sanctum issues SPA session cookies; refresh on roll-over.
- Services: SPIFFE workload identity via Istio sidecar; mTLS enforced; the application reads peer identity from the X-Forwarded-Client-Cert header.
- External callers: Passport OAuth 2.1 client-credentials with audience-scoped JWTs; mTLS for SWIFT/eFASS/regulator portals.
- RBAC: `spatie/laravel-permission` model implements the BRD §14.3 matrix. Policies are auto-generated from a YAML policy file stored under `config/atheris-rbac.yaml` so audit can review them without reading PHP.
- Caching: policy decisions cached in Redis for the request scope; invalidated on role change.

### 9.4 Headline API Catalogue

**Unchanged from v1.0 §9.4.** Paths, methods and contracts are identical. Laravel route definitions:

```php
// modules/Screening/Routes/api.php
Route::middleware(['auth:sanctum', 'octane.scope', 'audit'])->group(function () {
    Route::post('/v1/screening/realtime', RealtimeScreeningController::class);
    Route::post('/v1/screening/batch', BatchScreeningController::class);
    Route::get('/v1/screening/hits', ListHitsController::class);
});
```

### 9.5 Kafka Topics

**Unchanged from v1.0 §9.5.** Same topic names, partitioning and retention. Laravel implementation: per-module producer classes (e.g., `App\Modules\Library\Kafka\InstrumentPublishedProducer`) and per-module consumers running under Horizon supervisors.

### 9.6 Error Model

**Unchanged from v1.0 §9.6.** All sync APIs return RFC 9457 ProblemDetail. Laravel `Handler::render()` is overridden to emit Problem Details JSON for all `JsonResponse` exceptions; the `traceId` is sourced from the OpenTelemetry context.

### 9.7 Idempotency, Replay and Ordering

**Unchanged from v1.0 §9.7.** Idempotency keys, idempotent Kafka producers, per-aggregate ordering. Laravel implementation: `IdempotencyKey` middleware that stores `(key, response)` in Redis for 24h.

---

## 10. Module-by-Module Technical Design (M01–M30)

For brevity, each module below states the **Laravel-specific design**; the functional behaviour (per the BRD) and the conceptual architecture (per v1.0 §10) are unchanged.

### 10.1 M01 — Regulatory Library & Horizon Scanning

- **Module:** `library` (Laravel).
- **Persistence:** Postgres schemas `library` + `obligations`; OpenSearch index `universe_v1` via Scout; S3 bucket `atheris-regulator-pdfs`.
- **Filament:** `InstrumentResource` for CRUD; bulk import action driven by `maatwebsite/excel` chunked import.
- **HTTP endpoints:** Same paths as v1.0 §10.1; controllers under `app/Modules/Library/Http/Controllers/`.
- **Horizon scanner:** Laravel scheduled command `php artisan atheris:library:scan` runs daily; per-regulator strategy classes implement an `HorizonSource` interface (`fetch`, `diff`, `download`, `index`). New documents enqueue an `IngestInstrumentJob` (Horizon `library` supervisor).
- **OCR:** Textract called via AWS SDK PHP; Tesseract fallback queued via a `ShellExecJob` to a sidecar pod (Tesseract is too heavy for in-Octane execution).
- **Change Management workflow:** Temporal workflow `RegulatoryChangeWorkflow` (PHP, in `atheris-workflow-worker`) with activities `triage`, `impactAssessment`, `implementationPlan`, `signOff`, `closure`.
- **Bulk import:** `BulkImportJob` chunked by 200 rows; results streamed back via Reverb for live progress display in Filament.
- **TR-M1.1:** 1,000-row XLSX import ≤5min via Horizon-parallelised chunks.
- **TR-M1.2:** Universe search p95 ≤2s served from OpenSearch with module-level Redis result cache.

### 10.2 M02 — Policy & Procedure Management

- **Module:** `policy`.
- **Persistence:** Postgres schema `policy`; S3 `atheris-policies` (versioned, immutable).
- **Filament:** `PolicyResource` with version history sub-relation; one-click "publish new version" action.
- **State machine:** `spatie/laravel-model-states` — `Draft → InReview → Approved → Published → InForce → UnderReview → Superseded`.
- **Watermarked PDF:** `RenderPolicyPdfJob` dispatched to a `pdf-render` queue; renders via LibreOffice headless invoked through a queued shell job; resulting PDF sealed to S3 with version, owner, watermark text and digital signature.
- **Annual review timer:** Temporal workflow `PolicyAnnualReviewWorkflow` with 60/30/7-day reminders.
- **Acknowledgements:** Bound by SCIM-synced user list from Keycloak; Filament dashboard widget per LOB.

### 10.3 M03 — Obligations Register & Control Mapping

- **Module:** `library` (shared with M01 since obligations belong to instruments).
- **Filament:** `ObligationResource` with linked-control, linked-policy, linked-return panels.
- **Heat map:** Materialised view `vw_obligation_heatmap` refreshed every 15 minutes by a scheduled job; rendered in `dashboard` module.
- **Unmapped-obligation surfacing:** Postgres view `vw_unmapped_obligations` exposed as a Filament table widget on the CCO dashboard.

### 10.4 M04 — RCSA / BRA

- **Module:** `crmp`.
- **Engine:** A `RiskScoringService` accepts a `RiskAssessment` data class and returns `RiskScore` (Likelihood × Impact). Supports 3×3 (Drafts H/M/L) and 5×5 matrices — chosen per institution config.
- **Workflow:** Temporal workflow `BraCycleWorkflow` — `plan → dataCapture → workshops → score → signOff → closed`, 30-day SLA.
- **Risk appetite:** `risk_appetite_threshold` table; breach detection emits `risk.appetite_breached` to Kafka.

### 10.5 M05 — Controls Testing and Continuous Monitoring

- **Module:** `controls`.
- **CCM:** `ccm-runner` is a Laravel scheduled artisan command per CCM rule (`php artisan atheris:ccm:run --rule=NN`); each rule is a class implementing a `CcmRule` interface with `query`, `threshold`, `kciName`. Triggered by `Schedule::command()->everyFiveMinutes()` (per the rule's cadence).
- **Sampling:** `SampleSizeCalculator` value object computes AICPA / ISA 530-aligned sizes.
- **Escalation:** `monitoring.overdue` event triggers an `EscalationListener` that walks the configured escalation tree (LOB → CCO → BRC) using Temporal timers for SLA-bound escalations.
- **TR-M5.1:** Control failure → issue ≤1 hour via in-process write of `Issue` row inside the same database transaction as the failing test record.

### 10.6 M06 — KYC & CDD/EDD/ODD

- **Module:** `kyc` + `customer`.
- **Connectors:** Each external identity provider has a Saloon connector class (`NibssBvnConnector`, `NimcNinConnector`, `CacConnector`, `FirsTinConnector`, etc.). Saloon provides retries, circuit-breaker hooks (via Resilience4j-equivalent Laravel package), and request/response logging.
- **Orchestration:** `OnboardCustomerController` (Octane-mode) uses Guzzle promises to fan out BVN + NIN + CAC + screening calls in parallel; the controller awaits them with a 2.5-second budget so the total p95 stays ≤3s.
- **PSC register:** `Psc` model with `customer_id` + `holder_id` + `percent_held`; CAC PSC submission via the `CacConnector::submitPsc()` method.
- **Risk-score model:** `CustomerRiskScoringService` reads a JSON scorecard; plug-points for a future ML scorer (the AML sidecar can also serve as an ML scoring endpoint via a separate FastAPI route).
- **Tipping-off safeguard:** Sanctions hits route to MLRO via a `screening.hit` event; the front-line UI sees only "Pending compliance review".

### 10.7 M07 — Sanctions, PEP and Adverse-Media Screening

- **Module:** `screening` (deployed as `atheris-octane-screening`).
- **Persistence:** Postgres `screening_*` tables; Redis hot list cache (full list loaded in memory per Octane worker on warmup); OpenSearch alert workbench index.
- **Algorithms:** Match algorithms implemented as PHP classes (`ExactMatcher`, `LevenshteinMatcher`, `JaroWinklerMatcher`, `SoundexMatcher`, `MetaphoneMatcher`, `YorubaPhoneticMatcher`, `HausaPhoneticMatcher`, `IgboPhoneticMatcher`). Critical paths use C-extension functions (`similar_text`, `levenshtein`); pure-PHP fallbacks live behind feature flags.
- **Latency:** Targeted at p95 ≤500ms. Octane worker pool pre-loads the list. If k6 shows the 500ms target is missed under projected load, `ADR-016` triggers fallback to `go-screening`.
- **List refresh:** Per-vendor Saloon connector polls the vendor API on schedule; on refresh, `ScreeningListRefreshedJob` rebuilds the in-memory index (broadcast via Reverb to all Octane workers) and emits `screening.list_refreshed`.
- **TFS 24-hour reporting:** `TfsReporterListener` filters `screening.hit` by NSC/UN/TPPA list source and starts a 24-hour Temporal timer; SLA breach escalates to MLRO/CCO.

### 10.8 M08 — AML Transaction Monitoring

- **Modules:** `aml` (proxy in Laravel) + `atheris-aml-rules` (Python sidecar).
- **Flow:** Laravel `aml` module receives transaction events from core banking via the `txn-ingest` Kafka consumer; publishes to `txn.ingested`. The Python sidecar consumes `txn.ingested`, evaluates rules, and emits `alert.raised` back to Kafka. Laravel `aml` module consumes `alert.raised` and writes to `aml.alert` and `aml.case` tables. The Laravel `aml` module does NOT evaluate rules itself.
- **Behavioural baseline:** Python service streams `txn.ingested` to compute per-customer baselines using rolling windows in Redis; deviation thresholds produce alerts (same as v1.0).
- **Tuning workbench:** Filament-based admin UI for rule tuning is implemented in Laravel; the back-end POSTs tuning changes to the Python sidecar via REST and stores them in `aml.tuning_change` for audit. Maker-checker enforced via Laravel policies.

### 10.9 M09 — STR / CTR / CDR / SAR — NFIU goAML

- **Module:** `nfiu`.
- **goAML XML:** Built using PHP DOM + XSD validation via `libxml`; `XsdValidator` returns line-numbered errors before submission.
- **Submission:** `NfiuGoAmlConnector` (Saloon) submits over HTTPS or via SFTP per NFIU spec; acknowledgement parsed and stored.
- **Tipping-off:** Postgres row-level security policy on `nfiu.str` restricts SELECT to `mlro`, `dep_cco` roles; Laravel adds a defence-in-depth `StrAccessPolicy` checked in every controller; access logged as a privileged event in `audit.event`.
- **CTR thresholds:** Nightly Laravel command aggregates `txn.ingested` against ₦5m / ₦10m thresholds; produces draft CTR rows for MLRO review.

### 10.10 M10 — Regulatory Returns Automation

- **Module:** `returns`.
- **Filament:** `ReturnDefinitionResource` + `ReturnRunResource` with submission action.
- **Auto-draft:** Each `ReturnDefinition` carries a `DraftStrategy` interface (PHP); per-return strategies pull data from owning modules via their `Services\*Client` and produce the regulator's expected payload (XML / XLSX / JSON / PDF). All strategies are version-pinned and unit-tested with golden files.
- **Submission:** Strategy pattern per regulator portal: `NfiuGoAmlStrategy`, `FirsTaxProMaxStrategy`, `CbnEfassStrategy` (or SFTP fallback `CbnEfassSftpStrategy`), `NdpcStrategy`, `SecEPortalStrategy`, `PenComRbsStrategy`, `NdicStrategy`.
- **Maker/checker:** Laravel policies enforce different users for draft, review and approval roles.
- **Day-1 load:** 187 returns from the Drafts (§19.4).

### 10.11 M11 — Consumer Protection and Complaints

- **Module:** `complaints`.
- **Filament:** Complaints triage table with SLA countdown column (Reverb-pushed updates so frontline agents see live SLA).
- **SLA timer:** Temporal workflow `ComplaintSlaWorkflow` (14-day CBN SLA, breach escalates).
- **CCMS integration:** `CbnCcmsConnector` (Saloon) imports/exports complaints in the CBN CCMS schema.

### 10.12 M12 — Data Protection (NDPA)

- **Module:** `dpo` + `incident`.
- **DPO console (Inertia/React):** RoPA, DPIA, DSR, breach views.
- **72-hour breach timer:** Temporal workflow `BreachNotificationWorkflow` activated on `breach.detected`; auto-drafts the NDPC notification XML; routes for DPO sign-off; submits to the NDPC portal; persistent timers ensure breach handling continues across pod restarts.
- **DSR:** Customer-portal-side intake emits an event; ticket auto-created; identity verification via existing BVN/NIN connectors; fulfilment within 30 days (Temporal timer).
- **Cross-border transfer register:** `dpa_transfer` table tracks country, basis (BCR/SCC/certification), safeguards.

### 10.13 M13 — Whistleblowing

- **Module:** `whistle`.
- **Persistence:** Restricted-access schema `whistle` with `pgcrypto` field-level encryption on `reporter_identity_blob` keyed by a CMK accessible only to the Ethics Officer + BAC Chair (HSM key policy enforced; Laravel adds defence-in-depth via `WhistlePolicy`).
- **Reporter protection:** No join from `whistle` to HRIS tables in the platform; if a reporter provides a name, it lives in the encrypted blob.
- **Intake channels:** Inertia web form, email gateway (`MailToWhistleJob` parses inbound), SMS connector (NCC-compliant short code via partner), voice hotline transcript ingest.

### 10.14 M14 — Vendor / TPRM

- **Module:** `vendor`.
- **Filament:** `VendorResource`, `VendorAssessmentResource`, `VendorCertificateResource`.
- **Certificate expiry:** Laravel scheduled job (`atheris:vendor:cert-expiry`) emits `vendor.cert_expiring` 60/30/7 days pre-expiry.
- **Sanctions screening:** Periodic batch via `screening` module.

### 10.15 M15 — Training, Attestation, Certification

- **Module:** `training`.
- **LMS integration:** SCORM/xAPI inbound via `LmsConnector` (Saloon); HRIS joiner event triggers enrolment in role-mandatory modules; expiry timers in Temporal.

### 10.16 M16 — Incident, Breach, Operational Risk

- **Module:** `incident`.
- **Filament:** `IncidentResource` with severity-driven escalation actions.
- **Regulator timers:** CBN cyber 4-hour internal / 24-hour external; NDPC 72h; SEC for capital-market events — all Temporal timers.

### 10.17 M17 — Compliance Calendar

- **Module:** `returns` (the calendar is a unified read model over `returns`, `crmp`, `controls`, `training`, etc.).
- **Filament:** Calendar widget with role-filtered view.
- **ICS export:** Signed-URL endpoint via Laravel's URL signing.

### 10.18 M18 — Dashboards, Board Pack, Regulator Portal

- **Module:** `dashboard`.
- **Persistence:** ClickHouse for high-cardinality aggregates; Postgres materialised views for KPI snapshots; refreshed on `*.published` / `*.submitted` events.
- **UI:** Inertia + React + ECharts for the CCO and Board dashboards; widgets reproduce the Drafts Risk Profile pivots.
- **Board-pack pipeline:** `BoardPackBuilderJob` reads KPI snapshots, renders a PPTX/PDF using a PHP PowerPoint library (`phpoffice/phppresentation`) + LibreOffice headless; signed off → immutable `evidence_item`.
- **Regulator portal (Phase 2):** Separate Kong consumer with read-only scopes; data slices exclude customer PII; sessions watermarked and time-boxed.

### 10.19 M19 — Audit Trail and Evidence Vault

- **Modules:** `audit`, `evidence` (cross-cutting), deployed as separate `atheris-audit-writer` service.
- **Hash chain:** Implemented via a Postgres trigger that computes `this_hash = encode(digest(prev_hash || canonical_row_bytes, 'sha256'), 'hex')`. The trigger forbids `UPDATE` and `DELETE` on `audit.event`. The Laravel `AuditWriter` service writes to `audit.event` via a database role that has only `INSERT` privileges; Octane workers cannot connect with that role.
- **Merkle sealing:** Nightly Laravel command `atheris:audit:seal-day` computes the period's Merkle root, signs it via CloudHSM (`AwsKmsClient::sign`), seals the signed root + manifest to S3 with Object Lock Compliance retention.
- **Replay:** `audit-replay-service` exposes a read-only Kafka topic that re-emits any event window for examiners or internal forensics; gated by an Internal-Audit-only RBAC role.

### 10.20 M20 — FATCA & CRS

- **Module:** `fatca`.
- **Reports:** FATCA IDES XML and CRS XML built via PHP DOM; submission via IDES (TLS+S/MIME) using `Symfony\Component\Mime\Crypto\SMimeSigner`; CRS via FIRS connector.

### 10.21 M21 — Tax Compliance Cockpit

- **Module:** `tax`.
- **TaxPro Max integration:** `FirsTaxProMaxConnector` (Saloon) with REST + SOAP modes; signed payloads.

### 10.22 M22 — Conduct Surveillance

- **Module:** `conduct` + `surveillance-ingest` (sub-component).
- **Surveillance ingest:** Consumes alerts from the bank's existing market-abuse engine via Kafka or REST; creates `surveillance_alert` rows; case-management UI in Inertia.

### 10.23 M23 — Open Banking

- **Module:** `openbank`.
- **Consent revocation:** Propagates via `consent.revoked` to integration points; effective in minutes (AC23.1).

### 10.24 M24 — ABAC

- **Module:** `abac`.
- **EFCC freezing-order workflow (WF-07):** Temporal workflow `EfccFreezingOrderWorkflow`: intake → core-banking freeze API call → MLRO/Legal review → response composition → audit packet. The core-banking freeze call uses a per-bank `CoreBankingFreezeConnector`. Latency target ≤30 minutes from intake to freeze (AC24.3).

### 10.25 M25 — Corporate Governance

- **Module:** `gov`.
- **Fit-and-proper workflow:** Temporal workflow includes CBN no-objection touchpoints with a webhook-driven completion event.
- **Filament:** Board composition, committee, RPT, AGM filing resources.

### 10.26 M26 — ESG & Sustainable Banking

- **Module:** `esg`.
- **GHG inventory:** Schema for Scope 1/2/3; periodic import from facilities and credit book via Saloon connectors.

### 10.27 M27 — Capital Market Compliance

- **Module:** `capmkt`.
- **Insider-trading controls:** When a window is closed, trading instructions tied to listed-on-NGX securities for insiders are blocked at the upstream trading system via an API hook (`TradingSystemBlockConnector`).

### 10.28 M28 — Sanctions & Penalties Knowledge Base

- **Module:** `sanctkb`.
- **Filament:** `SanctionResource` with full-text search, source/section/offence/party filters.
- **Day-1 load:** 417 lines from the Drafts (§19.5).
- **Exposure calculation:** Materialised view `vw_penalty_exposure` aggregates penalty amounts by theme/regulator/LOB.

### 10.29 M29 — Account Management

- **Module:** `account`.
- **Dormant scheduler:** Nightly Laravel command classifies accounts per CBN guidelines; produces `account.dormant` events; downstream blocks debits via core-banking API.

### 10.30 M30 — Cash Management

- **Module:** `cash`.
- **ATM KCIs:** Daily KCIs ingested from the bank's ATM-monitoring solution via Kafka or REST.

---

## 11. Integration Architecture

### 11.1 Internal Bank Systems

**Unchanged from v1.0 §11.1** in scope and direction. PHP-specific integration layer:

- All outbound HTTP via Laravel HTTP client (Guzzle-backed) with Saloon SDK-style wrappers; retries, circuit-breakers (`spatie/laravel-resilience` or custom middleware), per-connector logging.
- CDC from core banking (Finacle / T24 / Flexcube / BaNCS / Temenos) ingested via Debezium → Kafka → Laravel Horizon consumer.
- AML engine alerts consumed via Kafka.
- HRIS via SCIM 2.0 (Laravel package `dasundev/laravel-scim-server` or custom controller).
- SSO via Socialite → Keycloak → Azure AD / Okta.

### 11.2 External Regulator and Industry Systems

**Unchanged from v1.0 §11.2** in scope and contracts. PHP-specific clients:

- NIBSS BVN / Watch-list / ICAD: Saloon connectors; mTLS via Guzzle handler.
- NIMC NIN, CAC, FIRS TIN, NIS Passport, FRSC, INEC PVC: Saloon connectors.
- NFIU goAML: HTTPS via Saloon for submission API; SFTP fallback via `phpseclib/phpseclib` for batch.
- CBN eFASS / FinA / RBS: Saloon (where API) or SFTP.
- NDPC, SCUML, FATCA IDES, OECD CRS: Saloon / SFTP per regulator.
- SWIFT Alliance: bank's existing SWIFT gateway exposes a Kafka topic; Laravel consumes for payment screening.
- NIBSS NIP / RTGS: dedicated Kafka topic from the bank's NIP gateway.

### 11.3 Integration Patterns

- Hexagonal / Ports-and-Adapters: every external system has a `port` interface (`<System>ConnectorContract`) and a `<System>Connector` Saloon implementation; tests use Saloon's MockClient.
- Circuit breakers: per-connector Resilience middleware with default fail-fast at 50% error rate within 30s.
- Outbox pattern: writes to a per-module `*_outbox` table inside the local DB transaction; Debezium streams to Kafka.
- Idempotent consumers: every Horizon job deduplicates on `(aggregateId, eventVersion)` stored in Redis.
- Dead-letter queues: every Horizon supervisor has a DLQ tag; replay via a `php artisan horizon:replay` custom command.

### 11.4 Failure Modes and Degraded Operation

**Unchanged from v1.0 §11.4.**

---

## 12. API Catalogue (Per Module Endpoints)

### 12.1 Naming Conventions

**Unchanged from v1.0 §12.1.** Base path `https://api.atheris.{bank-domain}/v1/`; resource-oriented routing; cursor pagination; `traceId` in every response.

### 12.2 Worked OpenAPI Example — Real-time Screening (M07)

**The contract is unchanged from v1.0 §12.2.** The Laravel implementation:

```php
// modules/Screening/Http/Controllers/RealtimeScreeningController.php
final class RealtimeScreeningController
{
    public function __construct(
        private readonly ScreeningService $screening,
        private readonly AuditWriter $audit,
    ) {}

    public function __invoke(RealtimeScreeningRequest $request): JsonResponse
    {
        $start = hrtime(true);
        $idempotencyKey = $request->header('Idempotency-Key');
        $cached = Cache::get("idem:$idempotencyKey");
        if ($cached) return response()->json($cached);

        $result = $this->screening->screen(
            fullName: $request->validated('fullName'),
            dob: $request->validated('dob'),
            country: $request->validated('country'),
            subjectType: SubjectType::from($request->validated('subjectType')),
            referenceId: $request->validated('referenceId'),
        );

        $latencyMs = (int) ((hrtime(true) - $start) / 1_000_000);
        $payload = [
            'requestId' => $result->requestId,
            'decision' => $result->decision->value,
            'hits' => $result->hits->toArray(),
            'latencyMs' => $latencyMs,
        ];
        Cache::put("idem:$idempotencyKey", $payload, 86400);
        $this->audit->record('screening.realtime', $result);
        return response()->json($payload);
    }
}
```

### 12.3 Per-Module Endpoint Index

**Unchanged from v1.0 §12.3.** Same URLs, same methods, same modules. Routes are registered in `modules/<Name>/Routes/api.php`.

---

## 13. Security Architecture

### 13.1 Principles

**Unchanged from v1.0 §13.1.** Zero-trust, least privilege, segregation of duties, defence in depth, no security through obscurity.

### 13.2 Identity and Access Management

**Unchanged from v1.0 §13.2** in posture; Laravel-specific implementation:

- Humans: OIDC SSO to bank IdP (Azure AD / Okta) via Keycloak; Socialite drives the OIDC code flow with PKCE; Sanctum issues SPA session cookies (sameSite=Strict, HttpOnly, Secure). MFA required at the IdP. Session 30 minutes idle / 8 hours absolute.
- Services: SPIFFE / SPIRE workload identities; mTLS via Istio; the application reads peer identity from the SPIFFE-issued X.509 SVID header.
- External callers: OAuth 2.1 client-credentials via Laravel Passport; audience-scoped JWTs; mTLS for regulators/partners.
- Authorisation: `spatie/laravel-permission` with attribute overrides (LOB, region) via custom policies. Decisions are auto-generated from `config/atheris-rbac.yaml`; the YAML maps to the BRD §14.3 matrix.
- Privileged Access Management: All admin actions go through Vault-broker JIT elevation; commands recorded; ≤30 min sessions.
- Access recertification: Quarterly Laravel command (`atheris:identity:recertify`) creates a Filament workflow for managers to review.

### 13.3 Network Security

**Unchanged from v1.0 §13.3.** WAF at ALB with OWASP CRS; egress allowlist by FQDN; pod security `restricted`; default-deny network policies via Calico/Cilium.

### 13.4 Encryption

**Unchanged from v1.0 §13.4.** TLS 1.3 internal; Aurora/S3/MSK/OpenSearch encryption with HSM CMK; field-level AES-256-GCM with envelope DEKs. Laravel implementation per `ADR-018`:

```php
// app/Casts/Encryptable.php
final class Encryptable implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null) return null;
        $envelope = json_decode($value, true);
        $dek = AwsKms::decrypt($envelope['wrappedDek']);
        return openssl_decrypt(
            $envelope['ciphertext'], 'aes-256-gcm', $dek,
            OPENSSL_RAW_DATA, $envelope['iv'], $envelope['tag']
        );
    }

    public function set($model, string $key, $value, array $attributes): array
    {
        if ($value === null) return [$key => null];
        $dek = random_bytes(32);
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($value, 'aes-256-gcm', $dek, OPENSSL_RAW_DATA, $iv, $tag);
        $wrapped = AwsKms::encrypt($dek, keyId: config('atheris.kms.cmk'));
        return [$key => json_encode([
            'v' => 1,
            'wrappedDek' => $wrapped,
            'ciphertext' => $ciphertext,
            'iv' => $iv,
            'tag' => $tag,
        ])];
    }
}
```

Key rotation: annual for CMKs; on-demand for compromise; backward-compatible decryption via key version IDs stored in the envelope.

### 13.5 Secrets Management

**Unchanged from v1.0 §13.5.** HashiCorp Vault for secrets, dynamic DB credentials, third-party API keys. Laravel reads secrets at boot via the `vault-php` client and the `VaultEnvServiceProvider`; Octane workers re-read on lease renewal.

### 13.6 OWASP ASVS Level 2 — Laravel-Specific Mapping

| ASVS Chapter | Implementation |
|---|---|
| V2 Authentication | OIDC + MFA at IdP; Sanctum cookies; password policy (where local accounts permitted) enforced via Fortify |
| V3 Session | Sanctum cookies with sameSite=Strict, HttpOnly, Secure; CSRF tokens via Laravel's built-in middleware for state-changing browser routes |
| V4 Access Control | `spatie/laravel-permission` policies; server-side enforcement; authorization unit tests via Pest on every endpoint |
| V5 Validation | Form requests; `spatie/laravel-data` with strict types; allowlist input; no string-concat SQL (Eloquent / query builder) |
| V7 Error Handling | ProblemDetail responses via `Handler::render`; no stack traces to clients; structured logs server-side |
| V8 Data Protection | Field-level encryption (`Encryptable` cast); no PII in logs (`PiiRedactionProcessor` Monolog filter); response sanitisation in Filament resource policies |
| V9 Communications | TLS 1.3; HSTS preload via middleware |
| V10 Malicious Code | Snyk + OWASP DC (Composer + npm); SBOM via Syft; image signing (Cosign); Trivy scan in CI |
| V12 File and Resources | Signed S3 URLs; antivirus / CDR scan for uploads via ClamAV daemon called from a queued job |
| V14 Configuration | 12-factor configuration; secrets in Vault; no commit of `.env`; `php artisan config:cache` baked at image build |

### 13.7 Anti-Tipping-Off and STR Confidentiality (M09)

**Unchanged from v1.0 §13.7.** Postgres row-level security on `nfiu.str`; Laravel `StrAccessPolicy` defence-in-depth; access logged as privileged events in `audit.event`; notifications related to STRs go to a separate, role-restricted channel.

### 13.8 Cyber-Resilience Programme (For the Platform)

**Unchanged from v1.0 §13.8.** Annual CBN Risk-Based Cybersecurity Framework self-assessment; Cybercrime Act 2015 incident reporting via the `incident` module; annual external pen-test by a CBN-recognised firm; quarterly red-team exercises; STRIDE threat modelling per module at design time.

---

## 14. NFR Implementation (Laravel-Tuned)

### 14.1 Performance Budgets

| NFR | Target | Laravel Implementation |
|---|---|---|
| NFR1 Screening p95 ≤500ms | Octane (RoadRunner) workers pre-loaded with the in-memory list cache; Redis Bloom filter pre-screen; Guzzle async only where outbound calls are needed; if budget missed, fallback to `go-screening` per `ADR-016` | k6 |
| NFR2 Onboarding p95 ≤3s | Octane controller fans out BVN+NIN+CAC+screening via Guzzle promises with a 2.5s budget per call and circuit-breaker (Resilience middleware) | k6 with synthetic BVN/NIN mocks |
| NFR3 AML alert ≤30s p99 | Laravel `aml` module proxies to Python sidecar; alert write idempotent via `Cache::lock()` | k6 + stream replay |
| NFR4 Search p95 ≤2s | OpenSearch with warm shards; query templates with bounded depth via Scout custom builder | k6 |
| NFR5 Dashboard refresh ≤5m | Materialised views + Debezium CDC; cache invalidation on event; Reverb broadcast triggers UI refresh | Synthetic observe |
| NFR6 Bulk import 1k rows ≤5m | `maatwebsite/excel` chunked + Horizon-parallelised; idempotent writes | Load test |
| NFR7 Board pack ≤60s | Pre-aggregated KPI snapshots + parallel renderers via Horizon; `phpoffice/phppresentation` for PPTX | Stopwatch test |
| NFR8 Returns auto-draft ≤2m | Per-return strategy with pre-warmed read models; Octane-mode controllers | k6 |
| NFR9 Evidence pack ≤4h | Async assembly via Horizon `evidence` supervisor; signed S3 packaging | Stopwatch test |

**Octane warm-up rule:** Pods are NotReady to the LB until `OctaneWarmupListener` has resolved key services and primed the screening list cache; readiness probe is `/actuator/ready` (custom Laravel health endpoint).

### 14.2 Scalability

- Octane Horizontal Pod Autoscaler scales on Octane worker concurrency and request queue depth.
- Horizon supervisors scale on Redis queue depth.
- Aurora reader auto-scaling; reader endpoint for read-mostly Laravel routes.
- Kafka partitions: 24 on `txn.ingested` to support 1B txn/yr (NFR15).
- OpenSearch warm tier kicks in beyond 90 days.

### 14.3 Reliability and Resilience

See §15. Same RPO/RTO targets, same active-active topology.

### 14.4 Security NFRs

See §13.

### 14.5 Auditability

All audit events go through `atheris-audit-writer` synchronous write before the calling Octane request returns success; loss of the audit write is treated as failure of the user action (the controller throws and rolls back).

### 14.6 Localisation

UIs i18n-ready via Laravel's localisation primitives (`__()`) + `i18next` on the React side; first release ships `en-NG`; Hausa/Yoruba/Igbo locale packs for selected customer-facing artefacts as Phase 2. All dates stored as UTC; rendered in WAT. NGN primary plus configurable list.

### 14.7 Accessibility

WCAG 2.1 AA compliance verified with axe-core and Pa11y in CI; Filament 3 is AA-compliant out of the box; Inertia/React workbench screens audited per CI build.

### 14.8 Data Residency

Production region pinned via Terraform with AWS SCPs denying cross-region writes for prod accounts except DR replicas. S3 buckets carry replication rules pointing to the NDPA-bound DR bucket only. Cross-border vendor support sessions are evidence-only; no production data leaves NG without DPO sign-off.

---

## 15. Resilience and Disaster Recovery

### 15.1 Failure Domains and Targets

**Unchanged from v1.0 §15.1.**

### 15.2 Backup and Restore

**Unchanged from v1.0 §15.2.** Aurora PITR (35 days) + daily snapshots cross-region; S3 versioned + cross-region replicated; Object Lock prevents deletion; Kafka MirrorMaker 2; OpenSearch nightly snapshot.

The Laravel-specific note: Temporal cluster persistence (Cassandra or Postgres backend) is backed up nightly; workflow histories are recoverable for 7 years.

### 15.3 DR Runbook (Outline)

**Unchanged from v1.0 §15.3.**

### 15.4 DR Test Cadence

**Unchanged from v1.0 §15.4.** Tabletop quarterly; live failover annually; backup restore drill quarterly.

---

## 16. Observability

### 16.1 Three Pillars

- **Metrics:** Prometheus / Thanos via OpenTelemetry PHP SDK; Laravel Pulse provides Laravel-native insight into queue depth, slow routes, slow queries, cache hit rate; custom business metrics (`atheris_returns_submitted_total`, `atheris_screening_latency_ms`).
- **Logs:** Monolog structured JSON → Loki; `PiiRedactionProcessor` prevents PII leaks; retained 90 days hot + 5 years cold (S3 Object Lock).
- **Traces:** OpenTelemetry PHP SDK + Tempo; sample rate 5% in prod, 100% in non-prod; W3C Trace Context propagated.

Laravel Telescope is **disabled in production** and used only in non-prod for developer-side debugging.

### 16.2 SLOs

**Unchanged from v1.0 §16.2.** Same targets, same windows.

### 16.3 Alerting

**Unchanged from v1.0 §16.3.** Multi-window burn-rate alerts; critical → PagerDuty SRE; business alerts on overdue returns/STR, stale sanctions list, NDPC 72-hour timer < 24h remaining.

### 16.4 Dashboards (Grafana)

**Unchanged from v1.0 §16.4.** Plus a dedicated **Laravel Pulse** dashboard for the application team (queue health, slow routes, exception rates, Octane worker memory).

### 16.5 Audit vs. Operational Logs

**Unchanged from v1.0 §16.5.** Audit events live in `audit.event` and S3 sealed packages; operational logs are for SRE. The two never share storage.

---

## 17. DevOps and CI/CD

### 17.1 Repositories

- Monorepo with per-module directories under `modules/`, web/React assets under `resources/js/`, infrastructure under `infra/`.
- Trunk-based development; short-lived feature branches; squash merges.
- Conventional Commits; semantic versioning per module.

### 17.2 Build Pipeline (Laravel application)

1. `composer install --no-dev` (Composer 2.7+, with `composer.lock` committed)
2. `composer check-platform-reqs`
3. `php artisan key:generate --show` (sanity)
4. `vendor/bin/pint --test` (code style)
5. `vendor/bin/phpstan analyse --memory-limit=4G` (Larastan level 9)
6. `vendor/bin/psalm --threads=4` (second-opinion static analysis)
7. `vendor/bin/pest --parallel` (unit + feature; coverage ≥80% gate via PestPHP coverage plugin)
8. `vendor/bin/infection --threads=4 --min-msi=70` (mutation testing, financial-crime modules at MSI ≥85%)
9. SAST: SonarQube PHP plugin; quality gate A
10. SCA: `snyk test --severity-threshold=high` for Composer + npm
11. OWASP Dependency-Check (Composer support enabled)
12. `gitleaks detect` (secret scan)
13. `npm ci && npm run build` (React/Inertia + Filament assets)
14. Container build: hardened PHP 8.3 + RoadRunner base; reproducible build
15. `trivy image atheris-app:$SHA` (block High/Critical)
16. `cosign sign atheris-app:$SHA` + `syft atheris-app:$SHA -o spdx-json > sbom.json`
17. Integration tests: Testcontainers-PHP (Postgres, Redis, OpenSearch, Kafka, WireMock); `pest --testsuite=integration`
18. Contract tests: Pact for inter-module + sidecar contracts
19. `deploy-dev` via ArgoCD
20. E2E: Playwright + k6 smoke (≤30 critical user journeys; ≤5 minutes total)
21. Manual approval → `deploy-staging` → `deploy-prod`

### 17.3 Environments

**Unchanged from v1.0 §17.3.** Dev, test, staging, prod, DR.

### 17.4 Performance Test Plan

**Unchanged from v1.0 §17.4** in approach. k6 profiles for screening, onboarding, AML pipeline, returns submission, dashboard, plus an **Octane regression suite** that fails the build if any of the budgets in §14.1 regress by more than 5%.

### 17.5 Release Management

**Unchanged from v1.0 §17.5.** Quarterly major, monthly minor, weekly patch, hotfix on demand. Blue/green for stateless services; canary (5% → 25% → 100%) for `octane-screening` and `aml-rules`.

### 17.6 GitOps

**Unchanged from v1.0 §17.6.** ArgoCD reconciles cluster state from `infra/`. Drift detection alerts SRE.

### 17.7 Laravel-Specific Deployment Concerns

- `php artisan config:cache` and `php artisan route:cache` baked at image build to avoid first-request penalty.
- `php artisan migrate --force` gated behind a release-pipeline approval gate; never auto-run on pod start.
- `php artisan octane:reload` issued on rolling deploys to recycle workers without dropping requests.
- Composer autoload optimisation: `composer install --optimize-autoloader --classmap-authoritative`.
- OPcache pre-loaded with the framework + module classes.

---

## 18. Operations and Runbook Outlines

### 18.1 On-Call Model

**Unchanged from v1.0 §18.1.**

### 18.2 Runbooks (Headline)

R-001 through R-010 from v1.0 §18.2 carry forward unchanged. Two Laravel-specific additions:

- **R-011 Octane worker memory leak:** symptoms (RSS growth across requests), detection (Pulse memory chart), action (force worker recycle via `octane:reload`; if persistent, capture heap with `php-memprof`; rollback if confirmed leak).
- **R-012 Horizon supervisor stalled:** symptoms (queue depth rising, no progress), detection (Pulse + Horizon dashboard), action (restart supervisor via `horizon:terminate`; if persistent, fail-over to standby Horizon node).

### 18.3 Capacity Management

**Unchanged from v1.0 §18.3.**

### 18.4 Change Management

**Unchanged from v1.0 §18.4.**

---

## 19. Data Migration and Day-1 Load

### 19.1 Approach

A single **`day1-loader`** Laravel application reads the canonical Drafts XLSM (`Compliance Management Toolkits DRAFT.xlsm`) and produces a staged Postgres load with referential integrity.

- Driven by `maatwebsite/excel` chunked imports.
- Each sheet maps to a `<Sheet>Import` class implementing `ToModel`, `WithChunkReading`, `WithValidation`, `WithUpserts`, `SkipsOnFailure`.
- Each chunk dispatches a Horizon job (`day1-loader` supervisor) so the entire load is parallelisable.
- The loader is **idempotent**: re-running it skips rows whose `source_row_hash` already exists in the target with `staged_unchanged` status.
- The loader emits a `migration-report.xlsx` (via `maatwebsite/excel` export) enumerating each source row, target table, target ID, and validation status.
- A Filament dashboard widget shows live progress (powered by Reverb broadcasts).

### 19.2 Order of Operations

**Unchanged from v1.0 §19.2.**

1. Reference taxonomies — load `regulator` (43), `instrument_type` (13), `area_of_focus` (29), `nature_of_item` (4), `instrument_status` (3 + Exposure Draft), `risk_rating` (3), `crmp_theme` (12).
2. Instruments — 352 rows from `Compliance Universe`.
3. CRMP rows per theme — 12 theme sheets + master CRMP (1,227+ rows).
4. Monitoring activities — 236 rows.
5. Returns — 187 rows.
6. Sanctions — 417 rows.
7. Linkages — build `obligation_*_link` tables.
8. Index rebuild — OpenSearch via Scout `php artisan scout:import`.

### 19.3 Validation Rules at Load Time

**Unchanged from v1.0 §19.3.** Laravel-specific implementation: each Import class declares validation rules and a `prepareForValidation()` method; exceptions go to a `migration_exceptions` table with proposed fixes.

### 19.4 Day-1 Counts (Targets per the Drafts)

**Unchanged from v1.0 §19.4.** Same target counts and target tables.

### 19.5 Post-Load Day-1 Tasks

**Unchanged from v1.0 §19.5.** CCO walks the `migration-report.xlsx`; resolves exceptions in a Filament action; Library and CRMP heat-maps render; "Day-1 BRD acceptance pack" PDF auto-generated; Sanctions KB exposure dashboard renders.

### 19.6 Subsequent Migrations

**Unchanged from v1.0 §19.6.** Customer migration from core banking via separate `customer-loader` ingesting via REST/streaming.

### 19.7 Idempotency and Re-Runs

**Unchanged from v1.0 §19.7.** `source_row_hash` deduplication.

---

## 20. Compliance of the Platform Itself

**Unchanged from v1.0 §20.** The platform's own NDPA / NDPR / GAID posture, CBN Risk-Based Cybersecurity Framework alignment, Cybercrime Act, ISO 27001, ISO 22301, Money Laundering Act record-keeping, CAMA records-permanence, BOFIA banking-secrecy, Wolfsberg ABAC and FATF Compliance-Function obligations are all met by the same architectural controls — only the implementation language changes.

### 20.1 Platform-Specific NDPA Posture

**Unchanged from v1.0 §20.1.** Atheris vendor is data processor; signed DPA; own RoPA maintained; sub-processors registered.

### 20.2 Sub-Processor Register

**Unchanged from v1.0 §20.2.**

---

## 21. Risks, Open Decisions and Assumptions

### 21.1 Technical Risks (Updated for Laravel)

TR-R1, TR-R2, TR-R3 (rule complexity now in Python — same risk class), TR-R4 (goAML), TR-R5 (Temporal cluster — replaces Camunda risk), TR-R6 (Kafka), TR-R7 (OpenSearch), TR-R8 (Postgres migrations), TR-R9 (audit ledger), TR-R10 (sub-processor) carry forward from v1.0 §21.1. New / changed risks:

| ID | Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|---|
| **TR-R3-PHP** | Octane state leak across requests | M | M | `octane:check` linter; PHPStan custom rules; Pest test suite that exercises 1000+ rapid requests against each route in CI |
| **TR-R5-PHP** | Temporal cluster persistence corruption | L | H | Daily snapshot; PV replication; quarterly restore drills (same as Camunda risk) |
| **TR-R11** | Octane cannot meet screening p95 ≤500ms under projected load | M | H | `ADR-016` conditional fallback to `go-screening`; decision empirical at Phase-0 close |
| **TR-R12** | PHP ecosystem CVE in core dependency (Composer + npm) | M | M | Snyk + OWASP DC daily; emergency-patch playbook ≤24h for High/Critical CVEs |
| **TR-R13** | Tier-1 bank CIO/CISO procurement bias toward Java | L | M | Comparative reference-architecture document; production references; CBN cyber self-assessment evidence |
| **TR-R14** | Filament breaking change between minor versions | L | M | Pin minor version; vendor lock-step; comprehensive E2E coverage gates the upgrade |
| **TR-R15** | Laravel LTS support window shorter than v1.0 Java LTS expectation | L | M | Mitigate by tracking the latest LTS (Laravel 11 LTS through 2027; Laravel 13 LTS path planned) — upgrade plan in the release calendar |

### 21.2 Open Decisions

Carries forward OD-1 through OD-10 from v1.0 §21.2. Two added:

| ID | Decision | Owner | Target |
|---|---|---|---|
| OD-11 | Octane runner: RoadRunner (recommended), Swoole, or FrankenPHP | CIO + Eng | Phase 0 |
| OD-12 | Filament admin panel vs. building all back-office screens in Inertia/React | CIO + Eng + UX | Phase 0 |

### 21.3 Assumptions

Carries forward v1.0 §21.3 plus:

- PHP 8.3 and Laravel 11 LTS will receive security support through the platform's first three years.
- RoadRunner 2024.x is operationally acceptable to the bank's SRE team.
- Temporal community edition (self-hosted) is acceptable; if Temporal Cloud is preferred, an additional cross-border data-flow review is needed for the workflow histories that contain business state.

---

## 22. Technical Glossary

Carries forward v1.0 §22 plus:

| Term | Definition |
|---|---|
| Octane | Laravel package that runs the framework on a long-lived application server (RoadRunner / Swoole / FrankenPHP). |
| RoadRunner | High-performance Go-based application server for PHP, used by Octane. |
| Filament | A Laravel admin-panel framework providing tables, forms, actions, widgets and exports out of the box. |
| Inertia.js | A library that turns a Laravel + React/Vue app into an SPA-like experience without building a separate API client. |
| Saloon | A PHP HTTP SDK builder library used to wrap external services as typed connectors. |
| Larastan | A Laravel-specific extension to PHPStan that understands Eloquent and Laravel's facade magic. |
| Pest | A modern PHP testing framework built on top of PHPUnit. |
| Horizon | Laravel's queue worker supervisor for Redis-backed queues. |
| Pulse | Laravel's first-party application insights dashboard. |
| Temporal | A workflow orchestration platform; replaces Camunda in this variant. |
| Pint | Laravel's PHP-CS-Fixer wrapper (PSR-12 + Laravel preset). |
| Infection | Mutation-testing framework for PHP. |
| `nwidart/laravel-modules` | A Laravel package for organising the application into self-contained modules. |
| `spatie/laravel-permission` | A role/permission management package widely used in the Laravel ecosystem. |
| `spatie/laravel-data` | A package for typed, framework-aware DTOs. |
| `spatie/laravel-model-states` | A package for state machines tied to Eloquent models. |
| `maatwebsite/excel` | A Laravel package for reading and writing Excel/CSV files. |

---

## 23. Appendices

### Appendix A — Bill of Materials (Laravel Variant)

See §6.1. The full table is the BOM.

### Appendix B — Sample DDL

**Unchanged from v1.0 Appendix B.** All Postgres DDL carries forward verbatim. The Eloquent models in §8.1 are the Laravel layer above this DDL.

### Appendix C — Sample Laravel Artefacts

**C.1 — Filament Resource (Instrument, M01)**

```php
<?php
namespace App\Modules\Library\Filament\Resources;

use App\Modules\Library\Models\Instrument;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;

final class InstrumentResource extends Resource
{
    protected static ?string $model = Instrument::class;
    protected static ?string $navigationGroup = 'Library';
    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('source_title')->required()->maxLength(500),
            Forms\Components\Textarea::make('objectives'),
            Forms\Components\DatePicker::make('date_issue'),
            Forms\Components\DatePicker::make('date_commence'),
            Forms\Components\Select::make('regulator_id')->relationship('regulator', 'name')->required(),
            Forms\Components\Select::make('instrument_type_id')->relationship('instrumentType', 'name')->required(),
            Forms\Components\Select::make('nature_id')->relationship('nature', 'name'),
            Forms\Components\Select::make('status_id')->relationship('status', 'name'),
            Forms\Components\Select::make('risk_rating_id')->relationship('riskRating', 'name'),
            Forms\Components\Textarea::make('risk_rating_explain'),
            Forms\Components\Textarea::make('commercial_bank_relevance'),
            Forms\Components\Textarea::make('commercial_bank_compliance_context'),
            Forms\Components\Select::make('applicability')->options([
                'Yes' => 'Yes', 'No' => 'No', 'Partially' => 'Partially',
            ]),
            Forms\Components\TextInput::make('link_url')->url(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('source_title')->searchable()->limit(80),
            Tables\Columns\TextColumn::make('regulator.code')->badge()->sortable(),
            Tables\Columns\TextColumn::make('instrumentType.name')->badge(),
            Tables\Columns\TextColumn::make('areaOfFocus.name'),
            Tables\Columns\TextColumn::make('riskRating.name')->badge()
                ->color(fn ($state) => match($state) { 'High' => 'danger', 'Medium' => 'warning', default => 'success' }),
            Tables\Columns\TextColumn::make('status.name'),
            Tables\Columns\TextColumn::make('date_commence')->date(),
        ])
        ->filters([
            SelectFilter::make('regulator_id')->relationship('regulator', 'name'),
            SelectFilter::make('instrument_type_id')->relationship('instrumentType', 'name'),
            SelectFilter::make('risk_rating_id')->relationship('riskRating', 'name'),
            SelectFilter::make('status_id')->relationship('status', 'name'),
        ])
        ->headerActions([
            Tables\Actions\Action::make('bulkImport')
                ->label('Bulk import (XLSX)')
                ->form([Forms\Components\FileUpload::make('file')->required()])
                ->action(fn (array $data) => dispatch(new \App\Modules\Library\Jobs\BulkImportInstrumentsJob($data['file']))),
        ]);
    }
}
```

**C.2 — Horizon Job (Bulk Import, M01)**

```php
<?php
namespace App\Modules\Library\Jobs;

use App\Modules\Library\Imports\InstrumentImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

final class BulkImportInstrumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'library';
    public int $timeout = 600;
    public int $tries = 3;

    public function __construct(private readonly string $filePath) {}

    public function handle(): void
    {
        Excel::import(new InstrumentImport(), $this->filePath);
    }
}
```

**C.3 — Temporal Workflow (NDPC Breach 72h, M12)**

```php
<?php
namespace App\Modules\Dpo\Workflows;

use App\Modules\Dpo\Activities\BreachActivitiesInterface;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
final class BreachNotificationWorkflow
{
    private BreachActivitiesInterface $activities;

    public function __construct()
    {
        $this->activities = Workflow::newActivityStub(
            BreachActivitiesInterface::class,
            \Temporal\Activity\ActivityOptions::new()->withStartToCloseTimeout(\Carbon\CarbonInterval::minute(5))
        );
    }

    #[WorkflowMethod]
    public function run(string $breachId): \Generator
    {
        yield $this->activities->draftNdpcNotification($breachId);

        // 24-hour reminder
        yield Workflow::timer(\Carbon\CarbonInterval::hour(24));
        yield $this->activities->remindDpo($breachId, '48h_remaining');

        // 48-hour reminder
        yield Workflow::timer(\Carbon\CarbonInterval::hour(24));
        yield $this->activities->remindDpo($breachId, '24h_remaining');

        // 72-hour boundary — submit or escalate
        yield Workflow::timer(\Carbon\CarbonInterval::hour(24));
        $submitted = yield $this->activities->isSubmitted($breachId);
        if (! $submitted) {
            yield $this->activities->escalateToCcoCio($breachId);
            yield $this->activities->flagSlaBreach($breachId);
        }
    }
}
```

**C.4 — Eloquent Model with Encrypted PII (M06)**

See §8.1 for the `Customer` model.

**C.5 — Sample Sequence Flow (text — Real-time Screening, M07)**

```
Payment Gateway --(POST /v1/screening/realtime, Idempotency-Key)--> Kong --(mTLS)--> atheris-octane-screening
  Controller(__invoke) -> ScreeningService::screen()
    RedisCache → in-memory list (≤2ms)
    Matchers (Exact, Levenshtein, JaroWinkler, Soundex, Metaphone, Yoruba/Hausa/Igbo phonetic) → score
    if hit:
      Kafka producer → screening.hit
      Listener (case-service) creates a case; MLRO workbench surfaces it
    audit-writer ← record (synchronous; failure rolls back response)
    return decision + hits + latencyMs
Total budget ≤500ms p95
```

### Appendix D — Mapping BRD Acceptance Criteria to Laravel Verifications

**Unchanged from v1.0 Appendix F.** Same AC IDs, same verification methods (k6 load tests, Pest integration tests, synthetic monitors, etc.).

### Appendix E — BRD Module to Laravel Module Map

| BRD `M<n>` | Laravel Module(s) | Service(s) |
|---|---|---|
| M01 | `library` | `atheris-app` (Filament) + Horizon `library` supervisor + Temporal workflow |
| M02 | `policy` | `atheris-app` (Filament) + state machine |
| M03 | `library` (obligations) | `atheris-app` (Filament) |
| M04 | `crmp` | `atheris-app` (Filament) + Temporal workflow |
| M05 | `controls` | `atheris-app` + scheduled CCM commands + Horizon |
| M06 | `kyc` + `customer` | `atheris-octane-screening` (orchestration) + `atheris-app` (CDD UI) |
| M07 | `screening` | `atheris-octane-screening` (hot path) |
| M08 | `aml` | `atheris-app` (workbench) + `atheris-aml-rules` (Python sidecar) |
| M09 | `nfiu` | `atheris-app` (workbench) + Saloon connector |
| M10 | `returns` | `atheris-app` (Filament) + Horizon supervisor |
| M11 | `complaints` | `atheris-app` (Filament + Inertia triage) |
| M12 | `dpo` | `atheris-app` + `atheris-workflow-worker` (Temporal 72h timer) |
| M13 | `whistle` | `atheris-app` (restricted-role Filament) |
| M14 | `vendor` | `atheris-app` (Filament) |
| M15 | `training` | `atheris-app` |
| M16 | `incident` | `atheris-app` + Temporal timers |
| M17 | `returns` (calendar read model) | `atheris-app` (Filament widget) |
| M18 | `dashboard` | `atheris-app` (Inertia + ECharts) + ClickHouse |
| M19 | `audit` + `evidence` | `atheris-audit-writer` (separate deployment) |
| M20 | `fatca` | `atheris-app` |
| M21 | `tax` | `atheris-app` + Saloon TaxPro Max |
| M22 | `conduct` | `atheris-app` |
| M23 | `openbank` | `atheris-app` |
| M24 | `abac` | `atheris-app` + Temporal freezing-order workflow |
| M25 | `gov` | `atheris-app` + Temporal fit-and-proper workflow |
| M26 | `esg` | `atheris-app` |
| M27 | `capmkt` | `atheris-app` |
| M28 | `sanctkb` | `atheris-app` (Filament) |
| M29 | `account` | `atheris-app` + scheduled dormant classifier |
| M30 | `cash` | `atheris-app` |

### Appendix F — Initial Engineering Team Shape (Reduced from v1.0)

Where v1.0 estimated ~50 engineers, the Laravel variant runs leaner because the monolith model reduces operational and integration overhead.

| Squad | Members | Focus |
|---|---|---|
| Platform / SRE | 4 | EKS, observability, security, DR — same as v1.0 |
| Laravel core team (modules M01–M05, M10, M14, M15, M17, M28) | 7 | Filament + workflows + APIs |
| Financial Crime (M06–M09) | 6 | Octane screening + AML sidecar + NFIU integration + KYC orchestration |
| Conduct & Governance (M11, M13, M22, M24, M25) | 4 | Filament + workflows |
| Data & DPO (M12, M16) | 3 | Temporal + connectors + DSR workflows |
| ESG / Capital Market / Account / Cash / Open Banking (M23, M26, M27, M29, M30) | 4 | Filament + integrations |
| Tax / FATCA / CRS (M20, M21) | 2 | TaxPro Max + IDES |
| Frontend (Inertia + React workbench screens) | 3 | Shared with squads above; design-system steward |
| Python sidecar (AML rules + ML) | 3 | FastAPI + Kafka + ML feature engineering |
| Go sidecar (optional, only if `ADR-016` triggers) | 1–2 | Hot-path screening |
| QA | 4 | Pest, Pact, k6, Playwright |
| Total | ~36–40 | — |

### Appendix G — Phase Plan

**Unchanged from v1.0 Appendix F (note: v1.0 had this as Appendix F; in v1.1 it's Appendix G).** Eight phases, same outcomes. The Laravel variant typically compresses Phase 1 and Phase 4 by ~20% because of Filament; gains in Phase 5 are smaller because workbench screens still need Inertia/React.

---

**End of Document**


