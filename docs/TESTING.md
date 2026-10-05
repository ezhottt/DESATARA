# TESTING & ACCEPTANCE CRITERIA — DESATARA

**Document:** `docs/TESTING.md`
**Version:** 1.0
**Status:** CONTROLLED BASELINE
**Product:** DESATARA — Platform Pengelolaan Aset Desa
**Parent:** Master Blueprint DESATARA v3.0
**Upstream Contracts:** RTM v1.0 · PRD v1.0 · Workflow v1.0 · RBAC & Regulatory Authority Matrix v1.0 · ERD v1.0 · Data Dictionary v1.0 · UI/UX Specification v1.0 · Security Specification v1.0
**Target Stack:** Laravel · Inertia.js · Vue 3 · Tailwind CSS · PostgreSQL
**Primary Principle:** Evidence-Based Release · Risk-Based Testing · P0 Zero Tolerance

---

# 1. PURPOSE

Dokumen ini menetapkan standar pengujian dan acceptance criteria DESATARA.

Tujuannya bukan sekadar memastikan:

> “fitur berjalan.”

Tetapi membuktikan bahwa sistem:

- menjalankan business rule yang benar;
- menjaga tenant isolation;
- menerapkan RBAC;
- membedakan permission dan regulatory authority;
- menjaga workflow;
- mempertahankan historical truth;
- aman terhadap abuse;
- dapat menangani concurrency;
- menghasilkan laporan yang konsisten;
- dapat dioperasikan;
- dapat dipulihkan;
- memenuhi baseline accessibility;
- siap digunakan pada lingkungan produksi.

---

# 2. QUALITY PRINCIPLE

Tidak ada fitur dianggap selesai hanya karena UI dapat digunakan.

Definition:

```text
Feature Complete
≠
Production Ready
```

Production readiness membutuhkan:

```text
Implementation
+ Automated Tests
+ Security Evidence
+ Data Integrity Evidence
+ Operational Evidence
+ Acceptance Evidence
```

---

# 3. SOURCE OF TEST REQUIREMENTS

Test harus dapat ditelusuri ke:

```text
Regulation
→ RTM
→ PRD
→ Workflow
→ RBAC / Authority
→ ERD / Data Dictionary
→ Security
→ UI/UX
→ Test Case
→ Evidence
```

Critical requirement tanpa test coverage dianggap incomplete.

---

# 4. TEST LEVELS

DESATARA menggunakan:

1. Unit Test
2. Feature Test
3. Integration Test
4. Database Integrity Test
5. Authorization Test
6. Authority Test
7. Tenant Isolation Test
8. Workflow Test
9. Security Regression Test
10. Import/Export Test
11. Reporting Test
12. Queue/Cache Test
13. Frontend Test
14. Accessibility Test
15. Performance Test
16. Migration Test
17. Backup/Restore Test
18. End-to-End Test
19. Production Smoke Test

---

# 5. PRIORITY CLASSIFICATION

### P0 — Critical

Failure dapat menyebabkan:

- cross-tenant leakage;
- unauthorized regulatory action;
- authentication bypass;
- private evidence exposure;
- corruption of finalized records;
- incorrect critical workflow execution;
- destructive data integrity failure;
- unrecoverable production data.

**P0 failure = RELEASE BLOCKER.**

### P1 — High

Failure dapat menyebabkan:

- significant functional failure;
- privilege escalation;
- inaccurate report;
- broken administrative workflow;
- major accessibility failure;
- important operational failure.

P1 must be fixed or receive explicit documented disposition before release.

### P2 — Medium

Non-critical defect with workaround or limited impact.

### P3 — Low

Minor visual/usability issue without material administrative impact.

---

# 6. ZERO-TOLERANCE RULE

Production release requires:

```text
Unresolved P0 = 0
```

No exception by verbal approval.

---

# 7. TEST ENVIRONMENT

Automated test environment should resemble production architecture sufficiently to detect production-relevant failures.

Primary DB test target:

**PostgreSQL**

SQLite-only success is insufficient evidence for PostgreSQL-specific behavior.

---

# 8. TEST DATA MODEL

Minimum fixtures:

```text
Tenant A
Tenant B

User A1
User A2
User B1

Platform Admin

Tenant Admin
Kepala Desa
Sekretaris Desa
Pengurus Aset
BPD / Monitoring
Auditor
Viewer

Valid Authority
Expired Authority
Future Authority
Revoked Authority
No Authority

Active Tenant
Suspended Tenant

Valid Support Grant
Expired Support Grant
Revoked Support Grant
```

---

# 9. MULTI-TENANT FIXTURE RULE

Tenant isolation tests must contain at least two populated tenants simultaneously.

Testing Tenant A against an empty Tenant B is insufficient.

Both tenants require overlapping-looking data:

```text
similar asset names
similar document names
similar report periods
similar user names
similar codes where tenant uniqueness permits
```

to expose accidental global queries.

---

# 10. UNIT TEST SCOPE

Unit tests target isolated domain logic such as:

- state transitions;
- authority validity;
- regulatory rule evaluation;
- classification helpers;
- money calculations;
- reconciliation logic;
- data-quality calculation;
- workflow guards;
- report calculations;
- token generation logic.

Unit tests must remain deterministic.

---

# 11. FEATURE TEST SCOPE

Feature tests verify Laravel application behavior through HTTP/application boundaries.

Examples:

- login;
- tenant switch;
- asset CRUD;
- document download;
- inventory workflow;
- approval;
- report finalization;
- import;
- export.

Feature tests must verify both:

```text
successful path
+
denied/invalid path
```

---

# 12. DATABASE INTEGRITY TESTS

Test database constraints for:

- FK;
- tenant-aware FK;
- uniqueness;
- date range;
- quantity/value constraints;
- required relationships;
- partial uniqueness where implemented;
- immutable history assumptions.

Application validation is not sufficient evidence of DB integrity.

---

# 13. TENANT ISOLATION — P0

Tenant isolation is the highest-priority regression suite.

For each tenant-owned domain verify:

| Domain      | Read | Create | Update | Delete/Cancel | Search | Export |
| ----------- | ---: | -----: | -----: | ------------: | -----: | -----: |
| Assets      |    ✓ |      ✓ |      ✓ |             ✓ |      ✓ |      ✓ |
| Documents   |    ✓ |      ✓ |      ✓ |             ✓ |      ✓ |      — |
| Photos      |    ✓ |      ✓ |      ✓ |             ✓ |      — |      — |
| Locations   |    ✓ |      ✓ |      ✓ |             ✓ |      ✓ |      — |
| Mutations   |    ✓ |      ✓ |      ✓ |             ✓ |      ✓ |      ✓ |
| Maintenance |    ✓ |      ✓ |      ✓ |             ✓ |      ✓ |      ✓ |
| Inventory   |    ✓ |      ✓ |      ✓ |             ✓ |      ✓ |      ✓ |
| Approvals   |    ✓ |      ✓ |      ✓ |             ✓ |      ✓ |      ✓ |
| Reports     |    ✓ |      ✓ |      ✓ |             ✓ |      ✓ |      ✓ |
| Audit       |    ✓ |      — |      — |             — |      ✓ |      ✓ |

Every applicable cross-tenant attempt must fail safely.

---

# 14. TENANT_ID TAMPERING — P0

Attempt changing `tenant_id` through:

- POST;
- PATCH;
- query;
- hidden field;
- JSON;
- import;
- manipulated frontend;
- route parameters.

Expected:

> Tenant ownership cannot be reassigned from client input.

---

# 15. DIRECT OBJECT ACCESS — P0

For every tenant-owned resource:

```text
Get valid Tenant B ID/UUID
→ authenticate Tenant A
→ request Tenant B resource
→ DENIED
```

Response must not reveal sensitive metadata.

---

# 16. INERTIA DATA LEAK — P0

Inspect raw Inertia response.

Unauthorized information must not merely be hidden visually.

It must not exist in serialized props.

---

# 17. AUTHENTICATION TESTS

Test:

- valid login;
- invalid login;
- logout;
- unauthenticated protected route;
- password reset;
- expired/invalid reset;
- session regeneration;
- authentication throttling.

---

# 18. SESSION TESTS

Verify:

- session rotates after authentication;
- logout invalidates session;
- tenant switch updates authoritative context;
- stale tenant context is not reused;
- session does not leak across users.

---

# 19. RBAC TEST MATRIX

Each permission must have:

```text
Allowed actor test
+
Denied actor test
```

Do not test only role labels.

Test resolved permissions.

---

# 20. ROLE MANIPULATION

Attempt:

- unauthorized role assignment;
- assigning higher privilege;
- assigning role across tenant;
- manipulating role ID;
- removing protected access improperly.

Server must reject unauthorized changes.

---

# 21. PERMISSION ≠ AUTHORITY — P0

Test actor with:

```text
required permission = YES
required regulatory authority = NO
```

Critical action must fail.

---

# 22. AUTHORITY VALIDITY — P0

Test:

```text
valid
expired
future
revoked
wrong scope
wrong tenant
```

Only valid authority may satisfy authority gate.

---

# 23. AUTHORITY RECHECK

For critical workflow:

```text
Actor has authority
→ approves
→ authority expires/revoked
→ attempts execution/finalization
```

System must re-evaluate authority according to workflow requirement.

---

# 24. AUTHORITY SNAPSHOT

After decisive action:

- change current official;
- change authority assignment;
- change role.

Historical action must continue showing original authority context.

---

# 25. SEGREGATION OF DUTIES — P0

Where self-approval is forbidden:

```text
User submits
→ same user attempts approval
→ DENIED
```

Alternate account with valid authority follows expected workflow.

---

# 26. PLATFORM SUPPORT ACCESS — P0

Test:

```text
No grant
Expired grant
Revoked grant
Wrong tenant
Wrong scope
Valid grant
```

Only valid support grant allows specified access.

Every support action must be auditable.

---

# 27. SUSPENDED TENANT

Test operational writes against suspended tenant.

Expected:

- ordinary mutation denied;
- explicit recovery/support operation follows defined policy;
- background jobs respect suspension policy.

---

# 28. WORKFLOW TRANSITION TESTS

For every critical workflow:

```text
State
×
Action
×
Actor
×
Authority
×
Evidence
```

must be tested.

---

# 29. GENERIC STATUS BYPASS — P0

Attempt direct mutation:

```text
status = APPROVED
status = FINALIZED
status = DISPOSED
```

outside domain action.

Expected:

**DENIED / impossible through supported application boundary.**

---

# 30. INVALID TRANSITIONS

Examples:

```text
DRAFT → FINALIZED
REJECTED → EXECUTED
FINALIZED → DRAFT
DISPOSED → ACTIVE
```

unless explicit workflow defines otherwise.

Invalid transitions must fail.

---

# 31. REVISION WORKFLOW

Test:

```text
SUBMITTED
→ REVISION_REQUESTED
→ corrected
→ RESUBMITTED
```

History must remain intact.

---

# 32. CANCELLATION

Test cancellation:

- before execution where allowed;
- after execution;
- after finalization.

Post-execution/finalization generic cancellation must fail unless dedicated reversal/correction workflow exists.

---

# 33. STALE STATE — P0

Two actors load same resource.

Actor A modifies/finalizes it.

Actor B submits stale action.

Expected:

```text
409 / safe conflict
```

or equivalent controlled failure.

No silent overwrite.

---

# 34. DOUBLE SUBMIT

Simulate:

- double-click;
- retry;
- duplicated HTTP request;
- repeated queue delivery.

Critical effect must occur once where idempotency applies.

---

# 35. ASSET REGISTRATION

Acceptance:

- valid asset can be registered;
- tenant assigned server-side;
- invalid classification rejected;
- invalid location rejected;
- invalid responsible party rejected;
- history initialized;
- audit written.

---

# 36. ACQUISITION HISTORY

Verify:

- authoritative acquisition record exists;
- denormalized asset summary remains consistent;
- correction does not erase provenance.

---

# 37. CLASSIFICATION HISTORY

Reclassification must:

- preserve previous assignment;
- create new assignment/history;
- update current projection safely;
- write audit.

---

# 38. LOCATION HISTORY

Initial placement must be recorded.

Mutation:

```text
Old Location
→ Mutation
→ New Location
```

must preserve history and update current location atomically.

---

# 39. RESPONSIBLE PARTY HISTORY

Changing responsible party must not overwrite history.

Current projection and assignment history must remain consistent.

---

# 40. CONDITION HISTORY

Condition change creates event/history.

Current condition is derived/denormalized projection.

Historical condition remains queryable.

---

# 41. LIFECYCLE HISTORY

Lifecycle transitions must create durable events.

Direct arbitrary lifecycle mutation is prohibited.

---

# 42. CONTROLLED CORRECTION

Correction must record:

- reason;
- actor;
- before;
- after;
- timestamp;
- approval/reference where required.

Silent correction fails acceptance.

---

# 43. DOCUMENT UPLOAD

Test:

- valid upload;
- unsupported extension;
- spoofed MIME;
- oversized file;
- invalid subject;
- unauthorized subject;
- cross-tenant subject;
- unsafe filename;
- path traversal attempt.

---

# 44. PRIVATE DOCUMENT — P0

Verify document cannot be accessed via:

- guessed URL;
- direct storage path;
- other tenant;
- unauthorized actor;
- copied identifier.

---

# 45. DOCUMENT SUPERSESSION

Replacing finalized evidence must create a new document/supersession relation.

Original evidence remains preserved.

---

# 46. PHOTO ACCESS

Asset photo inherits tenant/resource authorization.

Cross-tenant photo access is P0.

---

# 47. QR TOKEN TEST

Verify:

- token is opaque;
- random token invalid;
- revoked token invalid;
- rotated token behavior correct;
- active token unique where configured;
- no sequential asset enumeration.

---

# 48. PUBLIC QR PROJECTION — P0

Inspect public response.

Only allowlisted fields may appear.

Must exclude:

- internal IDs;
- tenant internals;
- private documents;
- authority data;
- audit data;
- sensitive metadata.

---

# 49. QR RATE LIMIT

Repeated abusive requests must trigger configured protection without breaking normal legitimate access.

---

# 50. INVENTORY SESSION CREATION

Verify:

- authorized actor;
- tenant scope;
- valid inventory period;
- valid scope;
- dataset freeze;
- reference timestamp;
- expected asset count/hash where implemented.

---

# 51. INVENTORY EXPECTED SNAPSHOT

After session freeze:

- modify asset master;
- open inventory item.

Expected snapshot remains historically unchanged.

---

# 52. INVENTORY OBSERVATION

Observed data must not directly overwrite asset master.

Observation creates inventory result/discrepancy.

---

# 53. INVENTORY DISCREPANCY

Test:

```text
MATCHED
MISSING
RELOCATED
CONDITION_MISMATCH
UNIDENTIFIED
NEWLY_DISCOVERED
DUPLICATE_SUSPECTED
```

according to supported result taxonomy.

---

# 54. NEWLY DISCOVERED OBJECT

A discovered object must not automatically become registered asset.

Required:

```text
Discovery
→ Review
→ Asset Registration Workflow
```

---

# 55. INVENTORY RECONCILIATION — P0

Reconciliation must:

- reference discrepancy;
- use controlled domain action;
- preserve history;
- not directly overwrite arbitrary asset fields;
- record actor/authority;
- create audit.

---

# 56. INVENTORY FINALIZATION — P0

Finalization fails when blocking discrepancy remains unresolved unless formally accepted by allowed workflow.

Acceptance/finalization must preserve actor and authority evidence.

---

# 57. FINALIZED INVENTORY

Finalized inventory session is immutable.

Later correction occurs through new reconciliation/correction history.

---

# 58. MAINTENANCE

Test both:

```text
Planned Maintenance
Recorded After-the-Fact Maintenance
```

where supported.

Historical maintenance must remain append-oriented.

---

# 59. USAGE DETERMINATION

Verify:

- correct period;
- valid assets;
- required authority;
- evidence;
- workflow;
- formal decision linkage where required.

No automatic prior-year carry without explicit rule.

---

# 60. UTILIZATION

Test utilization type against controlled regulatory values.

Invalid type rejected.

Required authority/evidence enforced.

---

# 61. SAFEGUARDING

Test administrative, physical, and legal safeguarding records.

Missing evidence may create data-quality/compliance indicator but must not automatically fabricate a legal conclusion.

---

# 62. VALUATION

Valuation must:

- preserve previous valuation;
- record date/method/valuer/reference;
- respect authority/workflow;
- not silently replace historical value.

---

# 63. TRANSFER — P0

Test:

- valid transfer type;
- permission;
- authority;
- required external approval;
- formal decision;
- evidence;
- execution;
- stale state;
- duplicate execution.

Asset state/history must remain consistent.

---

# 64. DISPOSAL — P0

Required path:

```text
Request
→ Validation
→ Classification
→ Required Review
→ Approval
→ Formal Decision
→ Execution
→ DISPOSED
```

Test bypass attempts at every step.

No administrative DELETE substitutes disposal.

---

# 65. FORMAL DECISION

Formal decision registration requires genuine reference/evidence.

Application must not generate false external/formal decision evidence.

---

# 66. EXTERNAL APPROVAL

External approval is distinct from internal approval action.

Tests must prove one cannot substitute for the other.

---

# 67. APPROVAL ACTION HISTORY

Approval actions are append-only.

Attempt to update/delete historical approval action must fail through ordinary application paths.

---

# 68. AUTOMATED ACTION

System-generated action must preserve:

- actor_type;
- initiator where applicable;
- correlation ID;
- idempotency identity.

---

# 69. REPORT GENERATION

Verify report uses:

- correct tenant;
- correct reporting period;
- authorized dataset;
- correct template version;
- correct regulatory context.

---

# 70. REPORT FINALIZATION — P0

Finalization creates immutable snapshot.

Snapshot must preserve:

- payload;
- hash;
- rendered artifact;
- template version;
- regulatory references;
- official/authority context.

---

# 71. REPORT HISTORICAL TRUTH

After report finalization:

- change asset;
- change official;
- change classification;
- change template.

Previously finalized report must remain unchanged.

---

# 72. REPORT REVISION

Correction creates:

```text
parent_report_id
revision_no
new snapshot
```

Old finalized revision remains intact.

---

# 73. REPORT CONCURRENCY

Two simultaneous finalization attempts must not create conflicting final states.

---

# 74. PDF/EXCEL ACCEPTANCE

Generated files must verify:

- correct tenant;
- correct period;
- expected columns/sections;
- no unauthorized fields;
- correct historical snapshot;
- usable file format.

---

# 75. CSV FORMULA INJECTION

Export values beginning with spreadsheet formula triggers must be safely handled.

Regression tests include:

```text
=
+
-
@
```

where relevant.

---

# 76. IMPORT PREVIEW

Before confirmation:

```text
domain write count = 0
```

Preview displays validation and duplicate results without committing business records.

---

# 77. IMPORT CONFIRMATION

After confirmation:

- only valid/accepted rows written;
- configured atomic/partial strategy respected;
- provenance references import batch;
- audit generated.

---

# 78. IMPORT TENANT TAMPERING — P0

File containing another tenant identifier cannot redirect imported records to that tenant.

---

# 79. IMPORT ERROR REPORT

Invalid rows must produce understandable row-level error evidence.

---

# 80. INTEROPERABILITY EXPORT

Allowed states:

```text
NOT_PREPARED
READY_FOR_EXPORT
EXPORTED
RECONCILED
```

System must not claim `SYNCED` without actual supported integration.

---

# 81. SEARCH

Search tests verify:

- tenant scope;
- permission;
- filters;
- pagination;
- sorting;
- no cross-tenant suggestions;
- no hidden unauthorized records.

---

# 82. SERVER PAGINATION

Large list endpoints must use server-side pagination.

Acceptance tests should detect accidental unbounded dataset rendering.

---

# 83. CACHE ISOLATION — P0

Populate equivalent cache for Tenant A and Tenant B.

Verify key/data separation.

No cross-tenant cache hit.

---

# 84. CACHE INVALIDATION

Change:

- role;
- permission;
- authority;
- membership;
- tenant state.

Cached authorization-sensitive output must not remain incorrectly valid.

---

# 85. QUEUE ISOLATION — P0

Run sequential jobs:

```text
Tenant A Job
Tenant B Job
Tenant A Job
```

Verify tenant context resets correctly after every execution.

---

# 86. STALE QUEUED JOB

Queue command.

Change authoritative state before execution.

Worker must revalidate and safely abort/adjust according to domain contract.

---

# 87. NOTIFICATION ISOLATION — P0

Notification must go only to valid recipient in correct tenant.

Cross-tenant notification is P0.

---

# 88. AUDIT LOG

Critical action test must assert expected audit event.

At minimum verify:

- actor;
- tenant;
- action;
- subject;
- timestamp;
- correlation where applicable.

---

# 89. AUDIT IMMUTABILITY

Ordinary application actor cannot modify or remove audit history.

---

# 90. AUDIT HISTORICAL REFERENCE

Deleting/archiving allowed domain projection must not erase audit subject identity.

---

# 91. REGULATORY TRACEABILITY

Critical business rule tests should identify corresponding requirement/business-rule code where practical.

Goal:

```text
Regulation
→ Requirement
→ Test
```

remains auditable.

---

# 92. REGULATORY VERSIONING

Change active regulation/business-rule version.

Historical finalized workflow/report must continue referencing its original applicable version.

---

# 93. LOCAL REGULATORY OVERLAY

Tenant/local rule:

- may strengthen/extend allowed configuration;
- cannot weaken mandatory national rule.

Attempted weakening must be rejected.

---

# 94. DOCUMENT NUMBERING

Concurrency test:

```text
multiple simultaneous allocations
```

must not create duplicate official number where uniqueness is required.

---

# 95. SOFT DELETE / ARCHIVE

Test domain-specific deletion policy.

Established historical records must not disappear through generic delete.

---

# 96. UUID

Public/interoperability references use stable UUID where required.

Sequential DB ID must not become an accidental public security boundary.

---

# 97. FRONTEND COMPONENT TESTS

Critical interactive components should test:

- state rendering;
- validation feedback;
- disabled/loading state;
- confirmation;
- modal/drawer behavior;
- pagination;
- filters;
- empty/error states.

---

# 98. RESPONSIVE ACCEPTANCE

Minimum target widths:

```text
360px
390px
768px
1024px
1440px
```

Critical tasks must remain usable.

---

# 99. MOBILE TABLE ACCEPTANCE

No critical data/action may become unreachable because desktop table is merely squeezed into mobile width.

Use responsive alternative defined in UI/UX specification.

---

# 100. KEYBOARD ACCESS

Critical workflows must be operable with keyboard.

Test:

- Tab;
- Shift+Tab;
- Enter;
- Space;
- Escape where applicable.

---

# 101. FOCUS MANAGEMENT

Modal/dialog test:

```text
Open
→ focus enters
→ focus trapped appropriately
→ close
→ focus returns to trigger
```

---

# 102. ACCESSIBLE NAMES

Interactive controls require accessible names.

Icon-only button without accessible name fails acceptance.

---

# 103. ERROR ACCESSIBILITY

Validation errors must be:

- visible;
- understandable;
- associated with relevant input;
- not communicated only by color.

---

# 104. COLOR CONTRAST

Critical text and interactive controls target WCAG 2.2 AA contrast requirements.

Exact automated/manual validation occurs during UI implementation.

---

# 105. MOTION

Core task must not depend on decorative animation.

Respect reduced-motion preference where significant animation exists.

---

# 106. LOADING STATE

Async actions must communicate loading/busy state and prevent unsafe duplicate submission.

---

# 107. EMPTY STATE

Core modules require meaningful empty states.

Empty state must not appear as application failure.

---

# 108. ERROR STATE

Network/server errors must:

- preserve safe state;
- provide understandable feedback;
- avoid exposing technical internals.

---

# 109. PERFORMANCE BASELINE

Performance testing focuses on realistic administrative workloads rather than synthetic vanity scores.

Measure:

- dashboard;
- asset list;
- asset detail;
- search;
- inventory;
- report generation;
- import;
- export.

---

# 110. QUERY PERFORMANCE

Detect:

- N+1;
- unbounded queries;
- missing pagination;
- repeated expensive aggregation;
- missing critical indexes.

---

# 111. QUERY COUNT REGRESSION

Critical pages should receive query-count regression checks where useful.

A feature change should not silently multiply DB queries.

---

# 112. LARGE DATASET

Seed representative scale sufficient to test:

- thousands of assets;
- historical mutations;
- documents;
- inventory items;
- audit logs.

Exact benchmark size may be refined during implementation.

---

# 113. LONG-RUNNING PROCESS

Large:

- import;
- export;
- report;
- notification batch;

should move to queue when synchronous processing becomes inappropriate.

---

# 114. PERFORMANCE ACCEPTANCE

No universal millisecond SLA is invented at this stage.

Before production, baseline measurements must be captured and approved for target infrastructure.

Regression against established baseline becomes actionable.

---

# 115. MIGRATION TESTS

Fresh database:

```text
migrate:fresh
```

must succeed in CI/test environment.

Upgrade path from supported prior release must also be tested once releases exist.

---

# 116. MIGRATION DATA INTEGRITY

Migration affecting existing domain data must prove:

- no unintended loss;
- tenant ownership preserved;
- FK valid;
- history preserved;
- rollback/recovery strategy documented.

---

# 117. MIGRATION ROLLBACK

Not every destructive migration is safely reversible.

Therefore each production migration requires explicit:

```text
Rollback
or
Forward-Fix
or
Restore Strategy
```

before deployment.

---

# 118. SEEDER SAFETY

Production seeders must not:

- overwrite tenant data;
- create insecure default credentials;
- reset roles unexpectedly;
- fabricate official administrative evidence.

---

# 119. BACKUP TEST

Verify backup captures:

```text
Database
Private Documents
Required Configuration/Metadata
```

according to operational plan.

---

# 120. RESTORE DRILL — RELEASE GATE

Restore backup into isolated environment.

Verify:

- database starts;
- application connects;
- tenants intact;
- assets intact;
- documents accessible;
- relationships valid;
- reports/history intact.

Backup without restore test is insufficient.

---

# 121. RECOVERY EVIDENCE

Record:

```text
Backup timestamp
Restore timestamp
Backup source
Restore target
Duration
Result
Verification
Issues
```

---

# 122. DEPENDENCY SCAN

CI/release process checks relevant Composer/npm dependency vulnerabilities.

Finding severity must be reviewed before release.

---

# 123. STATIC ANALYSIS

Backend should use appropriate static analysis/linting.

Frontend should use configured lint/type/build validation according to implementation stack.

---

# 124. FORMAT CHECK

Code formatting must be deterministic and CI-verifiable.

---

# 125. PRODUCTION BUILD

Production frontend build must succeed from clean source and locked dependencies.

---

# 126. CONFIGURATION TEST

Production readiness verifies:

```text
APP_ENV
APP_DEBUG
APP_URL
DB
CACHE
SESSION
QUEUE
FILESYSTEM
MAIL/NOTIFICATION
HTTPS assumptions
```

without exposing secret values.

---

# 127. SECURITY HEADER CHECK

Production smoke test checks required security headers/configuration.

---

# 128. WEB ROOT CHECK

Attempt public access to:

```text
.env
.git
storage/private
database dumps
backup files
source config
```

All unauthorized access must fail.

---

# 129. E2E CRITICAL JOURNEYS

Minimum E2E journeys:

### Journey A — Asset

```text
Login
→ Tenant
→ Create Asset
→ Add Evidence
→ View Detail
```

### Journey B — Mutation

```text
Asset
→ Mutation
→ Approval if required
→ Execute
→ Verify Location History
```

### Journey C — Inventory

```text
Create Session
→ Freeze Dataset
→ Verify Assets
→ Discrepancy
→ Reconcile
→ Finalize
```

### Journey D — Reporting

```text
Create Report
→ Review
→ Finalize
→ Snapshot
→ Export
```

### Journey E — Disposal

```text
Request
→ Review
→ Approval
→ Formal Decision
→ Execute
→ Verify History
```

---

# 130. NEGATIVE E2E JOURNEYS

At least one end-to-end journey must intentionally attempt:

- unauthorized access;
- cross-tenant resource;
- expired authority;
- stale state;
- missing evidence.

System must fail safely.

---

# 131. PRODUCTION SMOKE TEST

Immediately after deployment verify:

```text
Homepage/Login
Authentication
Tenant Context
Dashboard
Asset List
Asset Detail
Private File Authorization
Search
Critical Write
Queue
Cache
Audit
Report
Logout
```

Use safe dedicated smoke data where mutation is necessary.

---

# 132. POST-DEPLOY MIGRATION CHECK

After deployment verify:

- migration state;
- application health;
- queue worker;
- scheduler;
- storage;
- DB connectivity;
- logs;
- no immediate error spike.

---

# 133. ROLLBACK TEST

Release process must define:

```text
Application rollback
Database strategy
File compatibility
Queue compatibility
```

before risky deployment.

---

# 134. TEST EVIDENCE

Every release candidate should preserve:

```text
Commit SHA
Test command
Test result
Build result
Migration result
Security checks
Smoke test result
Deployment identifier
```

---

# 135. NO FALSE GREEN

Tests must fail when invariant is intentionally broken.

Critical test suites should periodically be validated against mutation/regression scenarios.

A test that cannot detect its intended defect is not useful evidence.

---

# 136. FLAKY TEST POLICY

Critical P0/P1 tests must not be routinely ignored as flaky.

Flaky tests require:

```text
Investigate
→ Fix
or
Quarantine with documented owner/reason
```

Quarantined P0 coverage blocks release unless equivalent evidence exists.

---

# 137. SKIPPED TEST POLICY

Skipped tests must be visible.

Critical tests may not be silently skipped in release CI.

---

# 138. TEST ISOLATION

Tests must not depend on execution order.

Tenant context, cache, queue, session, and database state must reset appropriately.

---

# 139. CI QUALITY GATE

Recommended sequence:

```text
1. Dependency Validation
2. Formatting
3. Lint
4. Static Analysis
5. Frontend Validation
6. Unit Tests
7. Feature Tests
8. Database Integrity Tests
9. Authorization / Authority Tests
10. Tenant Isolation Tests
11. Workflow Tests
12. Security Regression
13. Import / Export Tests
14. Reporting Tests
15. Production Build
16. Migration Verification
17. E2E / Smoke on Staging
```

---

# 140. CI FAILURE POLICY

Any required gate failure:

```text
CI = FAILED
```

Do not mark release ready from partial green checks.

---

# 141. RELEASE CANDIDATE ACCEPTANCE

Release candidate requires:

```text
[ ] Clean known source SHA
[ ] Required tests PASS
[ ] P0 = 0
[ ] P1 disposition complete
[ ] Production build PASS
[ ] Migration verified
[ ] Dependency findings reviewed
[ ] Tenant isolation PASS
[ ] Authority tests PASS
[ ] Workflow tests PASS
[ ] Private file tests PASS
[ ] Queue/cache isolation PASS
[ ] Backup available
[ ] Restore drill PASS
[ ] Staging smoke PASS
[ ] Rollback strategy ready
```

---

# 142. FEATURE DEFINITION OF DONE

A feature is DONE only if:

```text
Requirement mapped
Implementation complete
Authorization complete
Validation complete
Tenant isolation verified
Happy path tested
Negative path tested
Audit added where required
UI states complete
Documentation updated
Regression tests PASS
```

---

# 143. MODULE ACCEPTANCE — ASSET

Asset module accepted when:

- tenant scoped;
- CRUD/business actions authorized;
- classification valid;
- acquisition history preserved;
- location history preserved;
- condition history preserved;
- responsible-party history preserved;
- evidence protected;
- QR protected;
- audit generated.

---

# 144. MODULE ACCEPTANCE — INVENTORY

Inventory accepted when:

- session scope freezes;
- expected snapshot preserved;
- observations do not overwrite master;
- discrepancies recorded;
- reconciliation controlled;
- unresolved blockers prevent finalization;
- finalization immutable;
- audit complete.

---

# 145. MODULE ACCEPTANCE — APPROVAL

Approval accepted when:

- permission enforced;
- authority enforced;
- SoD enforced where required;
- workflow version respected;
- external approval distinct;
- stale action rejected;
- action append-only;
- authority snapshot preserved.

---

# 146. MODULE ACCEPTANCE — REPORTING

Reporting accepted when:

- tenant scoped;
- period valid;
- template versioned;
- regulatory context preserved;
- official context snapshotted;
- finalized report immutable;
- revision supported;
- export authorized.

---

# 147. MODULE ACCEPTANCE — DOCUMENTS

Documents accepted when:

- private by default;
- upload validated;
- tenant relationship enforced;
- unauthorized download denied;
- finalized evidence immutable;
- supersession preserved;
- sensitive access auditable where required.

---

# 148. MODULE ACCEPTANCE — SECURITY

Security accepted when:

- authentication tested;
- tenant isolation P0 passes;
- RBAC passes;
- authority passes;
- IDOR tests pass;
- file tests pass;
- QR tests pass;
- queue/cache tests pass;
- rate limiting enabled;
- production config hardened.

---

# 149. MVP ACCEPTANCE

MVP cannot be declared complete until required MVP modules from PRD satisfy their module acceptance criteria.

A visually complete interface does not satisfy MVP acceptance.

---

# 150. PRODUCTION READINESS GATE

Final production decision:

```text
FUNCTIONAL          PASS
BUSINESS RULE       PASS
REGULATORY          PASS
TENANT ISOLATION    PASS
AUTHORIZATION       PASS
AUTHORITY           PASS
WORKFLOW            PASS
DATA INTEGRITY      PASS
SECURITY            PASS
UI/UX               PASS
ACCESSIBILITY       PASS
BUILD               PASS
MIGRATION           PASS
BACKUP              PASS
RESTORE             PASS
SMOKE               PASS
```

Any required `FAIL`:

```text
PRODUCTION READY = NO
```

---

# 151. FINAL ACCEPTANCE RULE

DESATARA may be declared production ready only from current machine-verifiable evidence.

Forbidden acceptance basis:

- “seharusnya aman”;
- “kelihatannya sudah benar”;
- “pernah dites”;
- “di lokal jalan”;
- “fiturnya sudah ada.”

Required basis:

> **Current reproducible evidence.**

---

# 152. DOCUMENT HANDOFF

This specification becomes input for:

```text
Implementation Plan
→ Source Code
→ Automated Test Suite
→ CI/CD
→ Pre-Launch Audit
→ Production Release Gate
```

Implementation Plan must map each implementation batch to relevant acceptance criteria.

---

# 153. LOCKED TESTING INVARIANTS

The following are locked:

1. **Cross-tenant leakage is P0.**
2. **Unauthorized regulatory action is P0.**
3. **Permission and authority require separate testing.**
4. **Critical workflows require negative-path tests.**
5. **Finalized administrative history must remain immutable.**
6. **Inventory cannot silently overwrite asset master.**
7. **Private evidence requires direct access tests.**
8. **Queue/cache/notifications are tenant-isolation surfaces.**
9. **Production uses PostgreSQL-relevant testing.**
10. **Backup is not accepted until restore succeeds.**
11. **Skipped/flaky critical tests cannot create false-green releases.**
12. **Production readiness requires reproducible current evidence.**

---

# 154. STATUS

**TESTING & ACCEPTANCE CRITERIA DESATARA v1.0 — LOCKED**

This document is the canonical verification and release-acceptance contract for DESATARA.


# 155. CURRENT VERIFICATION EVIDENCE - 2026-10-05

The B26/UI closeout branch was verified locally with current reproducible evidence:

- PHPUnit: **135 passed / 506 assertions**.
- Pint: **185 files PASS**.
- PHPStan/Larastan: **0 errors**.
- Vite production build: **PASS**.
- `git diff --check`: **PASS**.
- Focused B26 import suite: **5 passed / 30 assertions**, covering CSV preview/commit, XLSX end-to-end preview/commit, duplicate protection, permission/navigation, and friendly legacy-XLS rejection.

GitHub Actions PR run #8 discovered all four configured jobs but did not start their steps because GitHub reported an account-level billing lock. It must not be recorded as CI PASS. Rerun is required after the external account lock is cleared.


# 156. B26.1 UI / PRODUCT SURFACE HARDENING - 2026-10-05

Screenshot-driven verification found a Vue template branching defect and literal mojibake in active UI sources. B26.1 fixes template ref unwrapping for inventory, approvals, reports, master-data, and administration surfaces; localizes `fair` as **Rusak ringan** and responsible-party types to human-readable Indonesian; resolves responsible-party display names; and removes tracked mojibake from active Vue/controller sources.

Fresh post-fix evidence: PHPUnit **137 passed / 514 assertions**, Pint **186 files PASS**, PHPStan **0 errors**, Vite production build **PASS**, `git diff --check` **PASS**, and active-source mojibake scan **NONE**.


# 157. B26.2 ASSET LABEL & QR PRINTING - 2026-10-05

B26.2 adds permission-gated single and bulk physical asset-label preparation from the asset register/detail surface. Labels use the existing opaque public QR contract, include village name, asset name, asset code, and register number, support 50 x 30 mm and 60 x 40 mm physical sizes, and use an A4 print stylesheet.

Because active QR tokens are stored hash-only, preparing a new label rotates an existing active QR instead of attempting to recover plaintext. Batch preparation is atomic, tenant-scoped, limited to 100 assets per request, and protected by `documents.manage` plus operational-tenant middleware. The UI warns that old labels become invalid after preparation.

Fresh post-B26.2 evidence: PHPUnit **141 passed / 542 assertions**, Pint **188 files PASS**, PHPStan **0 errors**, Vite production build **PASS**, `git diff --check` **PASS**, runtime source hygiene **PASS**. Focused B26.2 coverage: **4 passed / 28 assertions** before the final full-suite run.
