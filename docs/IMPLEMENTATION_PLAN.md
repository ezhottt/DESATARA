# IMPLEMENTATION PLAN / ROADMAP — DESATARA

**Document:** `docs/IMPLEMENTATION_PLAN.md`  
**Version:** 1.1
**Status:** CONTROLLED BASELINE
**Product:** DESATARA — Platform Pengelolaan Aset Desa  
**Parent:** Master Blueprint DESATARA v3.0  
**Upstream Contracts:** RTM v1.0 · PRD v1.0 · Workflow Specification v1.0 · RBAC & Regulatory Authority Matrix v1.0 · ERD v1.1 · Data Dictionary v1.1 · UI/UX Specification v1.0 · Security Specification v1.0 · Testing & Acceptance Criteria v1.0
**Target Stack:** Laravel · Inertia.js · Vue 3 · Tailwind CSS · PostgreSQL  
**Architecture:** Modular Monolith · Shared Database / Shared Schema · `tenant_id` Isolation  
**Delivery Strategy:** Incremental · Dependency-First · Test-Gated · Security-by-Default

---

# 1. PURPOSE

Dokumen ini menjadi rencana implementasi resmi DESATARA.

Tujuannya menerjemahkan seluruh dokumen desain yang telah dikunci menjadi urutan pembangunan yang:

- dependency-aware;
- dapat diuji per tahap;
- tidak membangun fitur di atas fondasi yang belum stabil;
- menjaga tenant isolation sejak migration pertama;
- menjaga historical truth;
- memisahkan permission dari regulatory authority;
- menghindari big-bang implementation;
- memungkinkan regression testing sejak awal;
- menghasilkan sistem yang dapat dideploy secara bertahap.

---

# 2. IMPLEMENTATION PRINCIPLE

Urutan implementasi wajib mengikuti:

```text
Foundation
→ Security Boundary
→ Governance
→ Master Data
→ Asset Core
→ Historical State
→ Evidence
→ Lifecycle
→ Inventory
→ Workflow
→ Reporting
→ Interoperability
→ Hardening
→ Production
```

Bukan:

```text
UI
→ CRUD
→ Patch Security Later
```

---

# 3. PRIMARY RULE

Setiap batch harus memenuhi:

```text
Implement
→ Test
→ Review
→ Verify
→ Lock
→ Next Batch
```

Batch berikutnya tidak boleh digunakan untuk menyembunyikan kegagalan batch sebelumnya.

---

# 4. SOURCE OF TRUTH

Urutan authority dokumen:

```text
Master Blueprint
↓
Regulatory Traceability Matrix
↓
PRD
↓
Workflow Specification
↓
RBAC & Regulatory Authority Matrix
↓
ERD
↓
Data Dictionary
↓
UI/UX Specification
↓
Security Specification
↓
Testing & Acceptance Criteria
↓
Implementation Plan
↓
Code
```

Jika kode bertentangan dengan kontrak yang dikunci:

> kode yang diperbaiki.

Bukan dokumentasi yang diam-diam disesuaikan untuk membenarkan implementasi.

---

# 5. CHANGE CONTROL

Perubahan terhadap locked requirement harus dilakukan melalui:

```text
Finding
→ Impact Analysis
→ Document Revision
→ Implementation Change
→ Regression Test
```

Tidak boleh melakukan silent architecture drift.

---

# 6. DEVELOPMENT BASELINE

Target awal:

- Laravel;
- PHP sesuai versi Laravel yang dipilih saat bootstrap;
- PostgreSQL;
- Inertia.js;
- Vue 3;
- Tailwind CSS;
- Vite;
- session-based authentication;
- queue-ready architecture;
- private filesystem;
- automated tests;
- Git-based workflow.

Exact dependency version dipin pada saat bootstrap berdasarkan stable compatible release.

---

# 7. REPOSITORY STRUCTURE

Target high-level:

```text
app/
├── Actions/
├── Domain/
├── Enums/
├── Events/
├── Exceptions/
├── Http/
├── Jobs/
├── Models/
├── Policies/
├── Providers/
├── Rules/
├── Services/
└── Support/

database/
├── factories/
├── migrations/
└── seeders/

resources/
├── css/
├── js/
│   ├── Components/
│   ├── Composables/
│   ├── Layouts/
│   ├── Pages/
│   ├── Types/
│   └── Utils/
└── views/

routes/
tests/
docs/
```

Exact folder decomposition may evolve where Laravel conventions provide a cleaner solution.

---

# 8. DOMAIN ORGANIZATION

Avoid premature microservices.

DESATARA remains a **modular monolith**.

Suggested logical domains:

```text
Platform
Tenant
Identity
Authority
Regulation
Asset
Evidence
Lifecycle
Inventory
Workflow
Reporting
Interop
Audit
```

Domain boundaries are conceptual and architectural, not justification for unnecessary abstraction.

---

# 9. SERVICE / ACTION RULE

Critical domain mutations should pass through explicit domain actions/services.

Example:

```text
ExecuteAssetMutation
FinalizeInventorySession
ApproveRequest
ExecuteAssetDisposal
FinalizeAssetReport
```

Controllers should orchestrate HTTP concerns, not contain the full business workflow.

---

# 10. FRONTEND RULE

Vue is responsible for:

- presentation;
- interaction;
- client UX;
- displaying server state;
- expressing user intent.

Laravel remains authoritative for:

- authentication;
- tenant;
- authorization;
- regulatory authority;
- validation;
- workflow;
- persistence;
- audit.

---

# 11. MIGRATION STRATEGY

Migrations follow dependency order.

General sequence:

```text
Platform
→ Tenant
→ Identity/Membership
→ Role/Permission
→ Authority
→ Regulation
→ Master Data
→ Asset
→ Asset History
→ Evidence
→ Lifecycle
→ Inventory
→ Workflow
→ Reporting
→ Interop
→ Audit/Supporting Structures
```

Cross-domain FK dependencies must be planned before migration creation.

---

# 12. TENANT FK RULE

For critical tenant-owned relationships:

```text
tenant_id + referenced_id
```

is the default relationship boundary.

Physical implementation follows Data Dictionary.

Exceptions must be explicit.

---

# 13. MIGRATION SAFETY

Before production use:

- migrations must work from fresh database;
- migrations must be deterministic;
- migration order must be verified;
- production migration requires rollback/forward-fix/restore strategy.

---

# 14. SEEDING STRATEGY

Separate:

### System seed

For:

- permissions;
- controlled statuses;
- baseline regulatory data;
- system configuration.

### Development/demo seed

For:

- fake tenants;
- fake users;
- fake assets;
- workflow scenarios.

Demo data must never be automatically seeded into production.

---

# 15. TEST-FIRST SECURITY BOUNDARY

Tenant isolation tests must exist before broad tenant-owned feature development.

Core regression:

```text
Tenant A
cannot
read/write Tenant B
```

becomes permanent P0 suite.

---

# 16. ROADMAP OVERVIEW

Implementation is divided into **18 batches**.

| Batch | Scope | Status |
| --- | --- | --- |
| B00 | Repository & Runtime Foundation | **Selesai** |
| B01 | Authentication Foundation | **Selesai** |
| B02 | Tenant Boundary | **Selesai** |
| B03 | RBAC | **Selesai** |
| B04 | Officials & Regulatory Authority | **Selesai** |
| B05 | Regulatory Traceability | **Selesai** |
| B06 | Master Data & Classification | **Selesai** |
| B07 | Asset Core | **Selesai** |
| B08 | Historical Asset State | **Selesai** |
| B09 | Documents, Evidence & QR | **Selesai** |
| B10 | Lifecycle Operations | **Selesai** |
| B11 | Inventory & Reconciliation | **Selesai** |
| B12 | Workflow & Approval Engine | **Selesai** |
| B13 | Reporting | **Selesai** |
| B14 | Import, Export & Interoperability | **Selesai** |
| B15 | Dashboard, Search & UX Completion | **Selesai** |
| B16 | Security / Performance / Accessibility Hardening | **Selesai - local gate GREEN** |
| B17 | Production Readiness & Release | **Readiness implementation selesai; production evidence/release gate belum lengkap** |
| B18 | Application Shell & Navigation | **Verified locally - automated gates green** |
| B19 | Master Data Surface | **Verified locally - automated gates green** |
| B20 | Asset Surface | **Verified locally - automated gates green** |
| B21 | Lifecycle Surface | **Verified locally - automated gates green** |
| B22 | Inventory Surface | **Verified locally - automated gates green** |
| B23 | Workflow Approval Surface | **Verified locally - automated gates green** |
| B24 | Reporting & Interoperability Surface | **Verified locally - automated gates green** |
| B25 | Administration Surface | **Verified locally - automated gates green** |

B00-B17 implementation baseline telah tercapai. Production readiness tetap evidence-based dan fail-closed sesuai PRODUCTION-READINESS.md.

---

# 17. BATCH 00 — REPOSITORY & RUNTIME FOUNDATION

## Goal

Create clean, reproducible application foundation.

## Scope

- initialize Laravel;
- configure PostgreSQL;
- install/configure Inertia;
- Vue 3;
- Tailwind;
- Vite;
- testing baseline;
- code formatting;
- lint/static-analysis baseline;
- environment templates;
- filesystem structure;
- queue configuration baseline;
- CI skeleton.

## Do Not Build Yet

- asset CRUD;
- tenant business logic;
- regulatory workflows.

## Required Evidence

```text
Application boots
PostgreSQL connects
Frontend builds
Test suite runs
Production build runs
CI executes
```

## Exit Gate

**B00 PASS**

---

# 18. BATCH 01 — AUTHENTICATION FOUNDATION

## Goal

Secure global user identity.

## Scope

- users;
- login;
- logout;
- password reset;
- session handling;
- session regeneration;
- authentication rate limiting;
- secure authentication responses.

## Security Tests

- invalid login;
- brute-force throttling;
- unauthenticated protected routes;
- session fixation;
- logout invalidation.

## Exit Gate

Authentication regression suite green.

---

# 19. BATCH 02 — TENANT BOUNDARY

## Goal

Establish tenant isolation before tenant-owned modules exist.

## Scope

- tenants;
- tenant settings;
- tenant memberships;
- active tenant resolution;
- tenant switch;
- tenant status;
- tenant-aware route/resource loading;
- tenant context service;
- tenant-aware jobs baseline;
- tenant-aware cache baseline;
- platform support grant model.

## Mandatory Tests

```text
Tenant A → Tenant A = allowed
Tenant A → Tenant B = denied
Client tenant_id override = denied
Suspended tenant mutation = denied
Platform Admin without support grant = no implicit tenant authority
```

## P0 Gate

No cross-tenant read/write.

## Exit Gate

**Tenant Isolation Core PASS**

---

# 20. BATCH 03 — RBAC

## Goal

Implement technical permissions independently from legal/regulatory authority.

## Scope

- roles;
- permissions;
- role-permission mapping;
- membership-role mapping;
- policy/gate infrastructure;
- assignment restrictions;
- protected administrative screens.

## Default Roles

- Platform Admin;
- Tenant Admin;
- Kepala Desa;
- Sekretaris Desa;
- Pengurus Aset;
- BPD / Monitoring;
- Auditor / Pemeriksa;
- Viewer.

Roles are defaults, not hard-coded business authority.

## Required Tests

Every permission:

```text
allowed actor
+
denied actor
```

## Exit Gate

RBAC matrix verified.

---

# 21. BATCH 04 — OFFICIALS & REGULATORY AUTHORITY

## Goal

Implement the distinction:

```text
User
≠
Membership
≠
Role
≠
Official
≠
Authority
```

## Scope

- tenant officials;
- authority assignments;
- validity period;
- appointment references;
- authority resolver;
- scope validation;
- support grants;
- authority snapshot mechanism.

## Required Cases

- valid;
- expired;
- future;
- revoked;
- wrong tenant;
- wrong scope;
- missing authority.

## Critical Test

```text
permission = YES
authority = NO
→ regulated action DENIED
```

## Exit Gate

Authority resolution PASS.

---

# 22. BATCH 05 — REGULATORY TRACEABILITY

## Goal

Make regulatory/business-rule traceability executable.

## Scope

- regulations;
- regulation versions;
- provisions;
- business rules;
- business-rule versions;
- provision mapping;
- workflow rule mapping;
- report rule mapping;
- classification rule mapping;
- tenant regulatory overlay.

## Invariant

Tenant/local configuration cannot weaken mandatory national rules.

## Exit Gate

Critical rule can be traced:

```text
Regulation
→ Provision
→ Business Rule
→ System Contract
```

---

# 23. BATCH 06 — MASTER DATA & CLASSIFICATION

## Goal

Build stable reference data required by asset records.

## Scope

- classification schemes;
- classification versions;
- asset classifications;
- funding sources;
- units;
- hierarchical locations;
- responsible parties;
- numbering series baseline.

## Requirements

- versioned classification;
- hierarchical location;
- tenant scope;
- historical-safe references.

## Exit Gate

Master data ready for asset registration.

---

# 24. BATCH 07 — ASSET CORE

## Goal

Implement asset identity and acquisition provenance.

## Scope

- assets;
- asset UUID;
- asset code;
- asset name/description;
- primary classification;
- acquisition;
- funding source;
- quantity;
- value;
- asset subtype details;
- lifecycle baseline;
- optimistic locking.

## Subtypes

As defined by ERD/Data Dictionary, including relevant:

- land;
- building;
- other asset-specific details.

## Acquisition Rule

`asset_acquisitions` is authoritative provenance.

Asset-level acquisition values are projection/summary only where retained.

## Required Tests

- tenant ownership;
- invalid classification;
- invalid location;
- invalid responsible party;
- invalid amount/quantity;
- duplicate identifiers;
- concurrency.

## Exit Gate

Asset can be safely registered with valid provenance.

## Public Alpha Milestone

After B07 passes its own gates, the project may publish `v0.1.0-alpha` as a demo/developer preview. The minimum vertical slice is:

```text
Authentication -> Tenant Context -> RBAC/Authority Foundation -> Master Data -> Asset Registration -> Asset List/Detail
```

This alpha is explicitly **not production-ready** and does not waive B08-B17 requirements. It exists to make implementation progress testable by contributors before v1.0.0.

---

# 25. BATCH 08 — HISTORICAL ASSET STATE

## Goal

Prevent silent destruction of historical truth.

## Scope

- classification assignments;
- initial placement;
- asset mutations;
- responsibility assignments;
- condition events;
- lifecycle events;
- controlled asset corrections;
- current-state projections.

## Persistence Contract

Authoritative B08 history uses:

- `asset_classification_assignments` for initial classification and reclassification;
- `asset_mutations` for initial placement/location movement;
- `asset_responsibility_assignments` for responsibility history;
- `asset_condition_events` for condition history;
- `asset_lifecycle_events` for lifecycle transitions;
- `asset_corrections` for controlled administrative correction evidence.

Current projections on `assets` are `classification_id`, `current_location_id`, `current_responsible_party_id`, `condition`, and `lifecycle_status`. Historical tables/events remain authoritative for reconstructing prior state.

## Atomicity & Concurrency

History insertion and its current projection update occur in one database transaction. Operations use `assets.lock_version` (or an equivalent row-locking strategy justified by implementation) so a stale writer cannot silently replace a newer state. Idempotency keys are enforced where the physical contract provides them.

Initial registration must initialize classification, placement when present, responsibility when present, condition, and lifecycle history consistently with the asset projection.

B08 may create nullable `workflow_instance_id` columns for future linkage, but it does not populate them or implement workflow/approval semantics. Their FK becomes enforceable in B12 after `workflow_instances` exists.

## Correction Boundary

`asset_corrections` is not a generic JSON patch endpoint. Correctable fields are server-side allowlisted. Material correction follows WF-AST-003 and records reason, actor, before, after, timestamp, and workflow/reference context when applicable.

A correction involving classification, location, responsibility, condition, or lifecycle must use the corresponding domain history mechanism; existing historical rows are not rewritten.

## Required Tests

- initial history matches current projections;
- old classification preserved and only one open classification assignment exists;
- old location preserved and mutation/current location update is atomic;
- old responsible party preserved and current assignment remains consistent;
- old condition preserved and current condition matches latest event;
- lifecycle history preserved and arbitrary direct transition path is unavailable;
- cross-tenant history references rejected at DB/application boundary;
- stale mutation rejected without partial history/projection write;
- duplicate idempotent event does not create duplicate history;
- correction records reason/actor/before/after and cannot silently rewrite historical rows;
- transaction rollback leaves both history and current projection unchanged.

## Exit Gate

Historical state invariant PASS with PostgreSQL constraint tests and service-level atomicity tests.

---

# 26. BATCH 09 — DOCUMENTS, EVIDENCE & QR

## Goal

Implement secure evidence infrastructure before evidence-dependent workflows.

## Scope

- documents;
- private storage;
- document links;
- supersession;
- asset photos;
- MIME validation;
- file size rules;
- malware scan state;
- visibility/classification;
- storage state;
- QR token;
- QR rotation/revocation;
- public projection profile.

## Security Tests

- cross-tenant document access;
- IDOR;
- path traversal;
- MIME spoofing;
- direct storage access;
- revoked QR;
- QR enumeration;
- public projection leakage.

## Exit Gate

Private evidence boundary PASS.

---

# 27. BATCH 10 — LIFECYCLE OPERATIONS

## Goal

Implement core asset-management lifecycle operations.

## Scope

- usage determination;
- utilization;
- safeguarding;
- maintenance;
- valuation;
- transfer;
- disposal preparation;
- formal decisions;
- external approval references.

## Rule

Critical lifecycle operation must use explicit domain action.

Example:

```text
ExecuteAssetTransfer
ExecuteAssetDisposal
RecordAssetValuation
```

## No Direct Status Mutation

Forbidden:

```text
asset.status = DISPOSED
save()
```

outside authorized domain action.

## Exit Gate

Lifecycle domain invariants PASS.

---

# 28. BATCH 11 — INVENTORY & RECONCILIATION

## Goal

Build inventory as controlled verification, not mass-edit UI.

## Scope

- inventory sessions;
- scope snapshot;
- reference timestamp;
- dataset freeze;
- inventory items;
- expected snapshot;
- observed snapshot;
- discoveries;
- discrepancies;
- reconciliation;
- reconciliation actions;
- formal acceptance;
- finalization.

## Required Flow

```text
Create Session
→ Freeze Dataset
→ Observe
→ Compare
→ Discrepancy
→ Reconcile
→ Accept
→ Finalize
```

## Critical Invariant

Observation does not directly mutate asset master.

## New Discovery

```text
Discovery
≠
Asset
```

It becomes asset only through registration workflow.

## Exit Gate

Inventory P0 suite PASS.

---

# 29. BATCH 12 — WORKFLOW & APPROVAL ENGINE

## Goal

Centralize state transition and approval governance.

## Scope

- workflow definitions;
- workflow versions;
- step definitions;
- workflow instances;
- transition events;
- approval requests;
- approval steps;
- approval actions;
- SoD;
- authority snapshot;
- external approval linkage;
- subject concurrency baseline;
- idempotency.

## Runtime Pattern

```text
Intent
→ Authentication
→ Tenant
→ Membership
→ Permission
→ Authority
→ State
→ Business Rule
→ Evidence
→ Concurrency Lock
→ Transaction
→ Audit
→ Side Effect
```

## Constrained Polymorphism

Generic workflow subject must use controlled subject registry/resolver.

## Critical Tests

- direct approval bypass;
- self-approval;
- expired authority;
- stale request;
- duplicate approval;
- cross-tenant subject;
- workflow version mutation.

## Exit Gate

Workflow P0/P1 suite PASS.

---

# 30. DEPENDENCY NOTE — B10/B12

Some lifecycle actions require approval.

Implementation may therefore use an internal phased approach:

```text
B10A lifecycle domain primitives
B12 workflow engine
B10B connect regulated lifecycle execution to workflow
```

B10 is not considered fully accepted until workflow-dependent paths pass after B12.

This avoids circular implementation dependency.

---

# 31. BATCH 13 — REPORTING

## Goal

Produce historically reliable administrative reports.

## Scope

- reporting periods;
- report templates;
- template versions;
- asset reports;
- report snapshots;
- revision chain;
- PDF;
- spreadsheet export;
- finalization;
- report hashing;
- authority/official snapshot.

## Finalization Pattern

```text
Draft Dataset
→ Validate
→ Review
→ Finalize
→ Immutable Snapshot
→ Rendered Artifact
```

## Required Tests

After finalization modify:

- current official;
- asset;
- classification;
- template.

Old finalized report must remain unchanged.

## Exit Gate

Reporting immutability PASS.

---

# 32. BATCH 14 — IMPORT, EXPORT & INTEROPERABILITY

## Goal

Implement safe bulk operations and honest external interoperability.

## Scope

- import batches;
- import rows;
- import errors;
- preview;
- validation;
- confirmation;
- atomic/partial strategy;
- provenance;
- exports;
- interoperability export state;
- reconciliation.

## Import Pattern

```text
Upload
→ Parse
→ Validate
→ Preview
→ Confirm
→ Write
```

Before confirmation:

```text
Domain Writes = 0
```

## Interoperability States

```text
NOT_PREPARED
READY_FOR_EXPORT
EXPORTED
RECONCILED
```

Do not use `SYNCED` without real integration.

## Exit Gate

Import/export isolation and provenance PASS.

---

# 33. BATCH 15 — DASHBOARD, SEARCH & UX COMPLETION

## Goal

Complete user-facing experience after domain contracts are stable.

## Scope

- dashboard;
- global/section search;
- advanced filters;
- pagination;
- sorting;
- saved UI state where useful;
- breadcrumbs;
- empty states;
- loading states;
- error states;
- responsive tables/cards;
- accessibility polish;
- notification center where required.

## Dashboard Principle

Decision support, not card festival.

Surface:

- actionable asset issues;
- inventory progress;
- unresolved discrepancies;
- pending approvals;
- incomplete evidence;
- reporting status;
- data-quality indicators.

## Exit Gate

Core workflows usable desktop and mobile.

---

# 34. BATCH 16 — SECURITY, PERFORMANCE & ACCESSIBILITY HARDENING

## Goal

Attack and stress the complete system before production.

## Security Scope

- IDOR;
- tenant tampering;
- mass assignment;
- upload abuse;
- QR abuse;
- CSRF;
- session;
- XSS;
- CSV injection;
- rate limiting;
- queue isolation;
- cache isolation;
- notification isolation;
- secret/config exposure.

## Performance Scope

- N+1;
- query count;
- indexing;
- pagination;
- dashboard aggregation;
- large inventory;
- report generation;
- imports/exports;
- queue load.

## Accessibility Scope

- keyboard;
- focus;
- semantic structure;
- accessible names;
- error association;
- contrast;
- responsive behavior;
- reduced motion.

## Exit Gate

No unresolved P0.

P1 disposition complete.

---

# 35. BATCH 17 — PRODUCTION READINESS & RELEASE

## Goal

Prove production readiness with current evidence.

## Scope

- production environment;
- HTTPS;
- production config;
- PostgreSQL migration;
- queue workers;
- scheduler;
- private storage;
- logging;
- monitoring;
- backups;
- restore drill;
- staging smoke;
- production build;
- rollback plan;
- deployment;
- post-deploy smoke.

## Required Evidence

```text
Commit SHA
Test Result
Build Result
Migration Result
Security Result
Backup Result
Restore Result
Smoke Result
Deployment Identifier
```

## Exit Gate

```text
PRODUCTION READY = YES
```

only if all mandatory gates pass.

---

# 36. CROSS-BATCH AUDIT LOG STRATEGY

Audit infrastructure should be introduced early enough that critical features do not need retrofitting.

Recommended:

```text
B02 baseline audit infrastructure
B03+ domain events progressively audited
```

Before a batch is locked, its critical mutations must have required audit coverage.

---

# 37. CROSS-BATCH CONCURRENCY STRATEGY

Do not postpone concurrency until final hardening.

Introduce:

- `lock_version`/equivalent;
- transaction boundary;
- unique constraints;
- idempotency key;

when the relevant aggregate first appears.

---

# 38. CROSS-BATCH DOCUMENT STRATEGY

Generic evidence infrastructure is formally implemented in B09.

Earlier domains may define evidence relationships but should avoid production-critical evidence workflows until B09 is complete.

---

# 39. CROSS-BATCH AUTHORITY STRATEGY

Authority infrastructure is B04.

No regulated workflow may be declared complete using role checks as substitute for authority.

---

# 40. CROSS-BATCH REGULATORY STRATEGY

Business-rule codes should be introduced as implementation constants/configuration in traceable form.

Critical rule tests should reference corresponding rule identifier where practical.

---

# 41. CROSS-BATCH FRONTEND STRATEGY

Do not wait until B15 to create all UI.

Each batch includes minimum functional UI for its scope.

B15 performs:

- cross-module consistency;
- dashboard;
- navigation refinement;
- search;
- responsive completion;
- accessibility polish.

---

# 42. DESIGN SYSTEM IMPLEMENTATION

Establish early reusable components:

```text
AppShell
PageHeader
Breadcrumb
Button
Input
Select
Textarea
Checkbox
Radio
DateInput
CurrencyInput
Table
Pagination
Badge
Alert
Modal
Drawer
ConfirmDialog
EmptyState
LoadingState
ErrorState
FileUpload
Timeline
DescriptionList
```

Avoid premature component abstraction before a pattern is demonstrated.

---

# 43. FORM IMPLEMENTATION STANDARD

Forms use:

- server-side validation;
- field-level errors;
- preserved safe input;
- busy state;
- duplicate-submit protection;
- accessible labels;
- clear destructive confirmations.

---

# 44. ACTION IMPLEMENTATION TEMPLATE

Critical action should approximately follow:

```text
Authorize
↓
Resolve Tenant
↓
Resolve Authority
↓
Load Aggregate
↓
Validate State
↓
Validate Business Rule
↓
Validate Evidence
↓
Acquire Concurrency Protection
↓
Transaction
↓
Persist History
↓
Update Projection
↓
Audit
↓
Dispatch Safe Side Effect
```

Not every action needs every step, but omitted critical controls must be intentional.

---

# 45. TRANSACTION RULE

A transaction is required when partial completion could violate an invariant.

Examples:

- history + current projection;
- approval + workflow transition;
- report finalization + snapshot;
- inventory reconciliation + domain action;
- numbering allocation + official document creation.

---

# 46. SIDE-EFFECT RULE

External side effects should not create inconsistent DB state.

Prefer patterns such as:

```text
DB Commit
→ afterCommit event/job
→ external side effect
```

where appropriate.

---

# 47. IDEMPOTENCY

Prioritize idempotency for:

- approval execution;
- workflow execution;
- report finalization;
- transfer/disposal execution;
- imports;
- external exports;
- queue-triggered critical operations.

---

# 48. OBSERVABILITY IMPLEMENTATION

By production, system should expose sufficient evidence for:

- application errors;
- queue failures;
- scheduled-task failures;
- authentication abuse;
- tenant-boundary anomalies;
- critical workflow failures;
- storage failures.

Do not log secrets or sensitive document contents.

---

# 49. ERROR HANDLING

Domain failures should use predictable application errors.

Examples:

```text
AuthorizationDenied
AuthorityInvalid
InvalidTransition
StaleResource
EvidenceMissing
TenantSuspended
ReconciliationRequired
```

Exact classes may differ.

User-facing messages must remain understandable.

---

# 50. HTTP ERROR EXPECTATIONS

Typical semantics:

```text
401 → unauthenticated
403 → unauthorized
404 → unavailable/not visible resource
409 → conflict/stale state
422 → validation/business input
429 → rate limited
```

Avoid leaking existence of cross-tenant resources.

---

# 51. API STRATEGY

First-party web remains Inertia/session based.

`/api/v1` is introduced only for justified:

- mobile;
- machine-to-machine;
- official integration;
- external consumer.

Do not duplicate all web controllers into API controllers without requirement.

---

# 52. API SECURITY

When API surfaces are introduced, they inherit:

- tenant isolation;
- permission;
- authority;
- validation;
- rate limiting;
- audit;
- versioning;
- idempotency where relevant.

---

# 53. PWA STRATEGY

Architecture remains PWA-ready.

MVP may support installability/basic caching where justified.

Unrestricted offline mutation is excluded unless a future synchronization/conflict-resolution specification is approved.

---

# 54. DEPENDENCY POLICY

Dependencies require:

- clear need;
- maintained package;
- compatible license;
- security review;
- version constraint;
- minimal privilege/surface.

Do not add package for trivial functionality already supported cleanly by framework/runtime.

---

# 55. THIRD-PARTY INTEGRATION POLICY

External service must have:

- explicit configuration;
- secret handling;
- timeout;
- retry policy;
- failure behavior;
- logging;
- tenant context where applicable.

---

# 56. CODE REVIEW GATE

Critical PR/change review asks:

```text
Does this change affect tenant scope?
Does it affect permission?
Does it affect authority?
Does it affect workflow?
Does it affect history?
Does it affect evidence?
Does it affect concurrency?
Does it affect audit?
Does it affect report correctness?
```

---

# 57. TEST REQUIREMENT PER BATCH

Every implementation batch includes:

```text
Unit where appropriate
Feature
Authorization
Tenant Isolation
Negative Paths
Database Integrity
Regression
```

plus domain-specific tests.

---

# 58. P0 REGRESSION SUITE

Permanent P0 suite includes at minimum:

```text
Cross-Tenant Read
Cross-Tenant Write
Cross-Tenant Document
Tenant-ID Tampering
Unauthorized Approval
Permission Without Authority
Self-Approval Where Forbidden
Expired Authority
Direct Workflow Bypass
Stale Critical Action
Duplicate Critical Execution
Private File Exposure
Public QR Leakage
Queue Tenant Leak
Cache Tenant Leak
Notification Tenant Leak
Finalized History Mutation
Inventory Direct Master Overwrite
```

---

# 59. TEST COMMAND BASELINE

Exact commands depend on final tooling, but CI must provide deterministic equivalents of:

```text
Backend formatting
Static analysis
Backend tests
Frontend lint/type checks
Frontend tests where configured
Production build
Migration verification
```

---

# 60. DOCUMENTATION-AS-CODE

The following live in repository:

```text
docs/
├── MASTER_BLUEPRINT.md
├── REGULATORY_TRACEABILITY_MATRIX.md
├── PRD.md
├── WORKFLOW_SPECIFICATION.md
├── RBAC.md
├── ERD.md
├── DATA-DICTIONARY.md
├── UI-UX.md
├── SECURITY.md
├── TESTING.md
└── IMPLEMENTATION_PLAN.md
```

Documentation changes are reviewed alongside affected code.

---

# 61. IMPLEMENTATION TRACEABILITY

For critical features, PR/commit should be traceable to:

- requirement;
- business rule;
- implementation batch;
- test.

Avoid excessive bureaucratic tagging for trivial cosmetic changes.

---

# 62. BRANCH STRATEGY

Recommended lightweight workflow:

```text
main
└── feature/<scope>
└── fix/<scope>
└── docs/<scope>
```

`main` should remain releasable or close to releasable.

---

# 63. COMMIT STRATEGY

Prefer small coherent commits.

Example:

```text
feat(tenant): add membership boundary
test(tenant): cover cross-tenant access
feat(asset): add acquisition provenance
fix(inventory): prevent direct asset overwrite
docs(workflow): clarify disposal execution
```

Avoid giant commits mixing unrelated domains.

---

# 64. PR / CHANGE GATE

Before integration:

```text
[ ] Scope understood
[ ] Tests added/updated
[ ] Formatting passes
[ ] Static analysis passes
[ ] Backend tests pass
[ ] Frontend checks pass
[ ] Build passes
[ ] Migration reviewed
[ ] Tenant impact reviewed
[ ] Security impact reviewed
[ ] Documentation updated if contract changed
```

---

# 65. DATABASE REVIEW GATE

Migration review checks:

- tenant ownership;
- composite tenant FK;
- indexes;
- uniqueness;
- check constraints;
- nullability;
- historical integrity;
- delete behavior;
- UUID;
- concurrency fields.

---

# 66. UI REVIEW GATE

New screen checks:

- information hierarchy;
- desktop;
- mobile;
- keyboard;
- focus;
- validation;
- empty;
- loading;
- error;
- authorization;
- destructive action confirmation.

---

# 67. SECURITY REVIEW GATE

Security-sensitive change checks:

- authentication;
- authorization;
- authority;
- tenant;
- input;
- output;
- files;
- secrets;
- logs;
- rate limit;
- concurrency;
- audit.

---

# 68. BATCH COMPLETION REPORT

Each batch should close with a compact evidence report:

```text
Batch:
Scope:
Commit SHA:
Migrations:
Tests:
Assertions:
Static Analysis:
Build:
Known Issues:
P0:
P1:
Decision:
```

---

# 69. NO CLAIM WITHOUT VERIFICATION

Forbidden completion language without evidence:

```text
"100% fixed"
"production ready"
"secure"
"all tests pass"
```

unless verified in current environment.

---

# 70. IMPLEMENTATION STOP CONDITIONS

Stop progression when:

- P0 regression exists;
- tenant boundary uncertain;
- migration integrity uncertain;
- authority model bypassed;
- historical data may be destroyed;
- tests cannot reproduce expected behavior.

Fix foundation before adding features.

---

# 71. MVP FUNCTIONAL MILESTONE

Functional MVP is reached after the required core path can execute:

```text
Tenant
→ Users/Memberships
→ Authority
→ Master Data
→ Asset Registration
→ Evidence
→ Asset History
→ Inventory
→ Approval
→ Reporting
```

but this does **not** automatically mean production ready.

---

# 72. SECURITY-COMPLETE MILESTONE

Reached when:

- P0 regression suite green;
- authorization matrix green;
- authority tests green;
- file/QR tests green;
- queue/cache isolation green;
- security hardening complete.

---

# 73. RELEASE-CANDIDATE MILESTONE

Reached after:

```text
Functional MVP
+
Security Complete
+
UX Complete
+
Performance Baseline
+
Migration Verification
+
Backup/Restore
```

---

# 74. PRODUCTION MILESTONE

Reached only after:

```text
Release Candidate
→ Staging
→ Full Gate
→ Deployment
→ Production Smoke
```

---

# 75. POST-LAUNCH PHASE

After stable v1 production:

Potential extensions include:

- planning/procurement;
- richer official-system interoperability;
- mobile client;
- advanced PWA;
- barcode support;
- analytics;
- richer document generation;
- enhanced regulatory engine;
- scheduled compliance review.

These are not reasons to weaken v1 architecture.

---

# 76. FIRST IMPLEMENTATION SESSION

After documentation lock, first coding session is strictly:

## B00 — Bootstrap

Tasks:

```text
1. Initialize repository/application
2. Pin runtime/dependencies
3. Configure PostgreSQL
4. Configure Inertia + Vue 3
5. Configure Tailwind
6. Configure test environment
7. Configure formatter/static analysis
8. Establish CI baseline
9. Establish docs directory
10. Run clean verification
```

No asset tables yet.

---

# 77. SECOND IMPLEMENTATION SESSION

Only after B00 passes:

## B01 — Authentication

Implement identity/session foundation and tests.

---

# 78. THIRD IMPLEMENTATION SESSION

Only after B01 passes:

## B02 — Tenant Boundary

This is the first major security milestone.

No broad asset development before B02 tenant isolation is proven.

---

# 79. IMPLEMENTATION PRIORITY

Priority is:

```text
Correctness
> Security
> Historical Integrity
> Maintainability
> Usability
> Performance Optimization
> Cosmetic Polish
```

This does not mean usability is optional; it means cosmetic work cannot override domain correctness.

---

# 80. ANTI-PATTERNS

Do not implement DESATARA as:

### CRUD-only architecture

```text
Controller
→ Model::update(request()->all())
```

### Role-only authorization

```text
if ($user->role === 'kepala_desa')
```

### Client-controlled tenancy

```text
tenant_id = request('tenant_id')
```

### Mutable finalized history

```text
report->snapshot = newData
```

### Inventory master editor

```text
inventory observation
→ assets.update()
```

### Public evidence storage

```text
/storage/evidence.pdf
```

### Generic status setter

```text
updateStatus($request->status)
```

These patterns violate the controlled architecture baseline.

---

# 81. IMPLEMENTATION SUCCESS CRITERIA

Implementation is successful when code demonstrates the contracts already designed.

Not when architecture is retroactively changed to match whichever code was easiest to write.

---

# 82. PRE-LAUNCH AUDIT

After B17 and before declaring production ready, perform independent full-system audit covering:

1. Logic & Business Flow
2. Security & Authorization
3. Regulatory Authority
4. Tenant Isolation
5. Validation
6. Database Integrity
7. Historical Integrity
8. Workflow
9. Evidence/Documents
10. Inventory
11. Reporting
12. Import/Export
13. Queue/Cache
14. Auditability
15. Error Handling
16. Edge Cases
17. Performance
18. Maintainability
19. UI/UX
20. Accessibility
21. Production/Server Readiness
22. Backup/Restore
23. Testing & Regression
24. Final Smoke Test

Audit is evidence-driven and read-only first.

---

# 83. RELEASE VERSIONING

Document versions are independent from application versions.

Pre-production public milestone after B07:

```text
DESATARA v0.1.0-alpha
```

This is a demo/developer preview, not a production-readiness claim.

Initial production application release remains:

```text
DESATARA v1.0.0
```

does not require every specification document to carry the same version number.

Example:

```text
Master Blueprint v3.0
PRD v1.0
ERD v1.0
Application v1.0.0
```

is valid.

---

# 84. SEMANTIC VERSIONING

Application release:

```text
MAJOR.MINOR.PATCH
```

General meaning:

```text
MAJOR → incompatible/major product contract change
MINOR → backward-compatible feature expansion
PATCH → backward-compatible fix
```

Exact release policy may be refined after first production release.

## Demo / Onboarding Data Strategy

Demo seed data must be synthetic and must not contain real village credentials, personal data, or private evidence. A complete onboarding dataset is introduced only after B02 Tenant Boundary and B03 RBAC are implemented, so tenant, membership, and role relationships are represented through the real security model rather than temporary shortcuts.

---

# 85. DEFINITION OF IMPLEMENTATION COMPLETE

DESATARA v1 implementation is complete only when:

```text
All required batches completed
+
All required acceptance gates passed
+
No unresolved P0
+
P1 disposition complete
+
Documentation synchronized
+
Backup verified
+
Restore verified
+
Production smoke verified
```

---

# 86. DOCUMENTATION BASELINE

With this document under controlled baseline, the pre-implementation documentation baseline consists of:

```text
Master Blueprint DESATARA v3.0
Regulatory Traceability Matrix v1.0
PRD DESATARA v1.0
Workflow Specification v1.0
RBAC & Regulatory Authority Matrix v1.0
ERD DESATARA v1.0
Data Dictionary DESATARA v1.0
UI/UX Specification v1.0
Security Specification v1.0
Testing & Acceptance Criteria v1.0
Implementation Plan / Roadmap v1.0
```

This becomes **DESATARA Pre-Implementation Baseline**.

---

# 87. NEXT EXECUTION STATE

Documentation phase:

```text
COMPLETE
```

Next phase:

```text
IMPLEMENTATION
```

First implementation target:

```text
B00
Repository & Runtime Foundation
```

No additional architecture document is required before bootstrap unless a contradiction is discovered during implementation.

---

# 88. CONTROLLED ROADMAP INVARIANTS

The following are baseline invariants and require explicit reviewed change:

1. **Tenant boundary is implemented before asset-domain expansion.**
2. **RBAC and regulatory authority remain separate.**
3. **Historical state is implemented before workflows depend on it.**
4. **Evidence infrastructure precedes evidence-critical workflow completion.**
5. **Inventory never becomes an uncontrolled asset editor.**
6. **Workflow state changes occur through explicit domain actions.**
7. **Concurrency and idempotency are implemented with their domains, not postponed.**
8. **Critical tenant/security regression tests exist permanently.**
9. **Reporting finalization produces immutable historical snapshots.**
10. **Backup requires successful restore evidence.**
11. **Every batch is independently verified before progression.**
12. **Production readiness is evidence-based.**

---

# 89. B18-B25 - PRODUCT SURFACE COMPLETION

Status: **IMPLEMENTED LOCALLY / AUTOMATED VERIFICATION GREEN**

Inertia/Vue product surfaces, route authorization, active-tenant shell, tenant-scoped master data and assets, lifecycle actions, inventory observation/reconciliation/finalization, workflow approval actions, reporting finalization/revision/export, interoperability import/export, and tenant administration are connected to the existing B00-B17 services and models. Focused surface tests and the complete serial verification gates pass on the current checkout. This does not change deployment or production-readiness status.

Current evidence on integrated `main`: PHPUnit **122 passed / 417 assertions**, Pint **180 files PASS**, PHPStan **0 errors**, Vite production build **574 modules transformed**, and `git diff --check` PASS. Checkpoint B18-B25: `332e1ee` (`feat: complete B18-B25 product surfaces`). Production readiness remains fail-closed and requires the mandatory external evidence in PRODUCTION-READINESS.md.

---

# 90. FINAL STATUS

**IMPLEMENTATION PLAN / ROADMAP DESATARA v1.0 — IMPLEMENTATION BASELINE COMPLETE**

B00-B16 telah selesai dan terverifikasi pada local quality gates. B17 production-readiness tooling dan runbook telah diimplementasikan. B18-B25 telah diverifikasi pada local automated gates.

Release production tetap **NOT READY / fail-closed** sampai mandatory external evidence pada PRODUCTION-READINESS.md terpenuhi, termasuk TLS, backup + isolated restore drill, queue, scheduler, production smoke, rollback/recovery path, dan unresolved P0 = 0.

Baseline B00-B25 telah terintegrasi ke `main`; B18-B25 masuk melalui checkpoint `332e1ee` dan telah diverifikasi ulang setelah fast-forward. Status integrasi source code tidak boleh disamakan dengan status deployment atau production readiness.

# 91. B26 - EXCEL IMPORT & LEGACY DATA MIGRATION

Status: **IMPLEMENTED LOCALLY / FINAL VERIFICATION GREEN**

B26 menambahkan onboarding data aset lama melalui CSV dan XLSX dengan preview sebelum commit, strategi atomic/partial, tenant-scoped import job/row/error, deteksi duplicate asset code, batas 5.000 baris dan 10 MB, template CSV, serta auto-mapping header aset desa berbahasa Indonesia. Format XLS lama ditolak dengan validation feedback yang meminta pengguna menyimpan ulang sebagai XLSX atau CSV.

Scope B26 sengaja tidak mengklaim mapping wizard bebas, saved mapping preset, background queue, atau dukungan binary XLS. XLSX dibaca tanpa mengeksekusi formula. Final focused evidence: B26 feature tests **5 passed / 30 assertions**, termasuk CSV preview+commit, XLSX end-to-end preview+commit, duplicate protection, permission/navigation, dan friendly legacy-XLS rejection.

Production readiness tetap mengikuti B17 dan tidak berubah oleh penyelesaian B26.

---


# 92. B26.2 - ASSET LABEL & QR PRINTING

**Status:** IMPLEMENTED LOCALLY / FINAL VERIFICATION GREEN

Delivered scope:
- single label preparation from asset detail;
- bulk selection from asset register;
- maximum 100 assets per batch;
- tenant-scoped and permission-gated preparation;
- opaque public QR URL using the existing QR security contract;
- atomic QR issue/rotation for the whole batch;
- explicit warning that previous QR labels are invalidated on rotation;
- 50 x 30 mm and 60 x 40 mm label sizes;
- A4 print layout;
- village name, asset name, asset code, and register number on each label;
- client-side QR rendering using the `qrcode` package.

This batch does not add barcode identifiers, recover stored QR plaintext, or weaken the existing public QR allowlist.
