# SECURITY SPECIFICATION — DESATARA

**Document:** `docs/SECURITY.md`  
**Version:** 1.0  
**Status:** CONTROLLED BASELINE
**Product:** DESATARA — Platform Pengelolaan Aset Desa  
**Parent:** Master Blueprint DESATARA v3.0  
**Upstream Contracts:** RTM v1.0 · PRD v1.0 · Workflow v1.0 · RBAC & Regulatory Authority Matrix v1.0 · ERD v1.0 · Data Dictionary v1.0 · UI/UX Specification v1.0  
**Architecture:** Laravel + Inertia.js + Vue 3 + PostgreSQL  
**Deployment Target:** Linux VPS / Cloud  
**Security Model:** Defense in Depth · Least Privilege · Deny by Default · Tenant Isolation · Server Authoritative

---

# 1. PURPOSE

Dokumen ini menetapkan baseline keamanan DESATARA untuk:

- application;
- database;
- tenant isolation;
- authentication;
- authorization;
- regulatory authority;
- workflow;
- documents;
- QR/public surfaces;
- import/export;
- queue;
- cache;
- logging;
- deployment;
- backup;
- incident handling;
- automated security regression.

Security bukan fitur tambahan.

Security merupakan invariant sistem.

---

# 2. SECURITY OBJECTIVES

DESATARA harus menjaga:

### Confidentiality

Data hanya tersedia kepada actor yang sah.

### Integrity

Data administratif tidak dapat dimodifikasi melalui jalur yang tidak sah.

### Availability

Data dan layanan dapat dipulihkan setelah kegagalan.

### Authenticity

Actor dan authority dapat dibuktikan.

### Accountability

Critical actions dapat ditelusuri.

### Tenant Isolation

Data tenant tidak pernah bocor ke tenant lain.

---

# 3. PRIMARY SECURITY INVARIANT

> **Tenant A must never be able to read, modify, infer, download, approve, export, search, cache-hit, receive notification about, or otherwise access Tenant B data.**

Pelanggaran invariant ini adalah:

**P0 SECURITY INCIDENT**

dan:

**RELEASE BLOCKER**

---

# 4. TRUST BOUNDARIES

Primary trust boundaries:

```text
Browser
   ↓
HTTPS
   ↓
Web Server / Reverse Proxy
   ↓
Laravel
   ↓
Authentication
   ↓
Active Tenant Resolution
   ↓
Membership
   ↓
Authorization
   ↓
Authority
   ↓
Validation
   ↓
Domain / Workflow
   ↓
PostgreSQL
```

Additional boundaries:

```text
Laravel → Private Storage
Laravel → Queue
Laravel → Cache
Laravel → Notification Provider
Laravel → Export Storage
Laravel → External Integration
```

Vue/Inertia berada di sisi untrusted client.

---

# 5. CLIENT TRUST MODEL

Browser dianggap untrusted.

Client dapat memanipulasi:

- form values;
- hidden fields;
- JavaScript;
- route parameters;
- tenant IDs;
- asset IDs;
- statuses;
- role values;
- HTTP requests.

Karena itu:

> **Tidak ada keputusan keamanan yang hanya bergantung pada Vue.**

---

# 6. SERVER AUTHORITY

Laravel menjadi authoritative boundary untuk:

- authentication;
- active tenant;
- membership;
- authorization;
- authority;
- validation;
- workflow transitions;
- business rules;
- regulatory rules;
- persistence;
- file access;
- reporting;
- audit.

---

# 7. THREAT MODEL

Threat categories minimum:

```text
Broken Access Control
IDOR / BOLA
Cross-Tenant Data Leakage
Privilege Escalation
Authority Bypass
Workflow Bypass
Mass Assignment
CSRF
XSS
SQL Injection
Unsafe File Upload
Sensitive File Exposure
QR Enumeration
Brute Force
Credential Stuffing
Session Abuse
Race Conditions
Replay / Duplicate Action
Import Abuse
Export Abuse
Cache Leakage
Queue Tenant Leakage
Sensitive Log Exposure
Misconfiguration
Secret Exposure
Backup Failure
Dependency Vulnerability
```

---

# 8. DATA CLASSIFICATION

Conceptual classes:

### PUBLIC

Explicitly approved public projection only.

Example:

limited QR lookup fields.

### INTERNAL

Normal administrative data.

### SENSITIVE

Examples:

- personal identifiers;
- authority records;
- internal documents;
- approval evidence;
- audit detail;
- security configuration.

### HIGHLY SENSITIVE

Examples:

- credentials;
- secrets;
- tokens;
- recovery secrets;
- encryption keys.

Default:

**INTERNAL / PRIVATE**

unless explicitly classified otherwise.

---

# 9. MULTI-TENANCY SECURITY MODEL

DESATARA uses:

```text
Shared Database
Shared Schema
tenant_id
```

Tenant isolation must therefore be enforced at multiple layers.

---

# 10. TENANT RESOLUTION

Request flow:

```text
Authenticate User
→ Resolve Active Tenant
→ Verify Membership
→ Establish Tenant Context
→ Authorize Resource
→ Execute Request
```

Client-supplied `tenant_id` is never authoritative.

---

# 11. TENANT_ID INPUT RULE

For normal tenant operations:

```text
request.tenant_id
```

must not determine ownership.

Canonical ownership:

```text
tenant_id = authenticated active tenant context
```

Mass assignment of tenant ownership is prohibited.

---

# 12. TENANT QUERY SCOPE

Tenant-owned queries must always be constrained to active tenant.

Unsafe conceptual pattern:

```text
Asset::find($id)
```

Preferred conceptual behavior:

```text
activeTenant
→ assets
→ find authorized resource
```

Exact implementation may use:

- scoped repository/query;
- tenant-aware model abstraction;
- domain service;
- policy;
- composite FK.

No reliance on developer memory alone.

---

# 13. TENANT DATABASE INTEGRITY

Critical tenant-owned relationships use tenant-aware integrity.

Conceptually:

```text
UNIQUE (tenant_id, id)
```

and child relationships:

```text
(tenant_id, asset_id)
→ assets(tenant_id, id)
```

where applicable.

Database reinforces application isolation.

---

# 14. TENANT ISOLATION SURFACES

Isolation applies to:

- pages;
- Inertia props;
- forms;
- route binding;
- search;
- filters;
- API;
- attachments;
- photos;
- QR;
- exports;
- reports;
- imports;
- approval;
- audit;
- notifications;
- cache;
- queues;
- jobs;
- scheduled commands;
- temporary files;
- public lookup.

---

# 15. CROSS-TENANT SUPPORT

Platform Admin does not automatically gain operational tenant access.

Cross-tenant support requires valid:

`platform_support_grants`

containing at minimum:

- target tenant;
- platform user;
- scope;
- reason;
- granted by;
- valid from;
- valid until;
- revocation;
- audit correlation.

Expired/revoked support grant denies access.

---

# 16. SUPPORT MODE

Support mode must be visually and technically explicit.

Every support-mode action is audited.

Support mode cannot silently become normal tenant membership.

---

# 17. TENANT SUSPENSION

Tenant state is authoritative.

When suspended:

- ordinary operational mutation blocked;
- login/read access follows configured policy;
- recovery/support operations require explicit permission;
- background jobs must respect suspension where applicable.

---

# 18. AUTHENTICATION BASELINE

First-party DESATARA uses session-based authentication.

JWT is not required for the Inertia application.

Authentication supports:

- login;
- logout;
- password reset;
- session invalidation;
- throttling;
- secure password handling.

---

# 19. PASSWORD STORAGE

Passwords must use Laravel-supported modern password hashing.

Plaintext password storage is prohibited.

Passwords never appear in:

- logs;
- audit payload;
- exports;
- analytics.

---

# 20. LOGIN RESPONSE

Authentication errors should avoid unnecessary account enumeration.

Rate limiting applies.

Repeated suspicious attempts generate security events when appropriate.

---

# 21. SESSION SECURITY

Production session requirements:

- HTTPS only;
- Secure cookie;
- HttpOnly;
- appropriate SameSite;
- server-controlled lifetime;
- session rotation after login;
- regeneration on privilege-sensitive changes where appropriate.

---

# 22. SESSION FIXATION

Successful authentication must rotate session identifiers.

Privilege/context transitions must not reuse unsafe session state.

---

# 23. LOGOUT

Logout invalidates the active authenticated session.

Sensitive tenant context must not survive incorrectly after logout.

---

# 24. TENANT SWITCH SECURITY

Tenant switch requires:

```text
authenticated user
+ valid membership
+ allowed tenant state
```

After switch:

- active tenant changes server-side;
- tenant-sensitive client state discarded;
- tenant-specific cached browser state not reused incorrectly.

---

# 25. AUTHORIZATION MODEL

Authorization consists of distinct layers:

```text
Authentication
↓
Membership
↓
Permission
↓
Resource Scope
↓
Regulatory Authority
↓
Workflow State
↓
Evidence / Business Rule
```

Passing one layer does not bypass another.

---

# 26. RBAC

Roles are permission bundles.

Security decisions should resolve permissions rather than hardcoded role names wherever possible.

Example:

```text
asset.view
asset.create
asset.update
inventory.finalize
approval.approve
report.finalize
```

---

# 27. ROLE ≠ AUTHORITY

Technical permission does not establish regulatory authority.

Example:

```text
approval.approve = TRUE
```

does not necessarily permit approval.

System must additionally verify required authority.

---

# 28. AUTHORITY CHECK

Critical action requires:

```text
Permission
AND
Valid Authority
AND
Correct Scope
AND
Authority Valid at Action Time
AND
Valid Workflow State
AND
Required Evidence
```

---

# 29. AUTHORITY SNAPSHOT

Decisive actions preserve historical authority context.

Snapshot must reconstruct:

```text
WHO
ACTED AS WHAT
UNDER WHICH AUTHORITY
AT WHAT TIME
```

Authority changes later must not rewrite historical evidence.

---

# 30. AUTHORITY REVALIDATION

Authority must be checked at:

- approval;
- execution;
- finalization;

where relevant.

Approval yesterday does not imply authority remains valid at execution today.

---

# 31. SEGREGATION OF DUTIES

Where workflow requires SoD:

- requester cannot approve own request;
- actor cannot bypass required intermediate step;
- server enforces the restriction.

UI hiding is insufficient.

---

# 32. POLICY / GATE REQUIREMENT

Resource operations must use centralized authorization mechanisms such as Laravel Policy/Gate/domain authorization.

Controllers should not accumulate ad hoc authorization logic.

---

# 33. DIRECT URL SECURITY

Direct route access must never bypass authorization.

Every resource request must re-evaluate:

- tenant;
- membership;
- permission;
- resource;
- authority where required.

---

# 34. IDOR / BOLA DEFENSE

Never assume UUID alone prevents IDOR.

For every object identifier:

```text
Resolve inside tenant
→ authorize
→ execute
```

Changing UUID/ID manually must not reveal another tenant's resource.

---

# 35. ROUTE MODEL BINDING

If Laravel route model binding is used, binding must remain tenant-aware for tenant resources.

Generic global binding that resolves cross-tenant resources before authorization must not create information leakage.

---

# 36. INERTIA PROP SECURITY

Only authorized data may be serialized.

Forbidden:

```text
Send sensitive data to Vue
→ hide with v-if
```

Correct:

```text
Authorize server-side
→ select safe fields
→ serialize
```

---

# 37. MASS ASSIGNMENT

Models/actions must use explicit writable fields.

Critical fields must never be user mass-assignable without controlled domain logic.

Examples:

- tenant_id;
- status;
- finalized_at;
- approved_by;
- authority snapshot;
- current lifecycle;
- audit fields.

---

# 38. INPUT VALIDATION

Every mutable request requires server-side validation.

Validate:

- type;
- format;
- length;
- range;
- ownership;
- existence;
- tenant scope;
- state;
- relationship;
- business rule.

Frontend validation is UX only.

---

# 39. SQL INJECTION

Use:

- Eloquent;
- Query Builder;
- parameter binding.

Dynamic raw SQL requires explicit review.

Never concatenate untrusted input into SQL.

---

# 40. XSS

Vue escaping and Laravel output escaping remain default.

Dangerous HTML rendering requires explicit sanitization/review.

Avoid arbitrary:

```text
v-html
```

with untrusted data.

---

# 41. CSRF

State-changing first-party requests require Laravel CSRF protection.

Do not disable CSRF globally to solve implementation problems.

External APIs receive separate authentication/security design.

---

# 42. OPEN REDIRECT

Redirect targets derived from user input must be constrained.

Authentication/workflow redirects should use server-known destinations.

---

# 43. WORKFLOW SECURITY

Critical state changes must use explicit domain actions.

Forbidden:

```text
PATCH status=APPROVED
```

as generic state mutation.

Required:

```text
ApproveRequest
FinalizeInventory
ExecuteMutation
FinalizeReport
```

or equivalent application/domain actions.

---

# 44. INVALID TRANSITION

Server rejects transitions not permitted by:

- current state;
- workflow version;
- permission;
- authority;
- evidence;
- business rules.

---

# 45. FINALIZED RECORD SECURITY

Finalized administrative records are immutable according to domain policy.

Correction occurs through:

- revision;
- correction;
- reversal;
- reconciliation;

not direct edit.

---

# 46. CONCURRENCY SECURITY

Critical operations require concurrency protection.

Examples:

- approval;
- inventory reconciliation;
- inventory finalization;
- report finalization;
- transfer;
- disposal;
- numbering;
- authority changes.

Use transaction + locking/version strategy appropriate to domain.

---

# 47. STALE STATE

Critical command must fail when operating on stale authoritative state.

Return safe conflict response.

Never silently overwrite newer state.

---

# 48. IDEMPOTENCY

Critical commands susceptible to retries/double-clicks must be idempotent where required.

Examples:

- finalize;
- execute;
- external callback;
- import confirmation;
- export generation.

Duplicate request must not duplicate administrative effect.

---

# 49. DOCUMENT STORAGE

All non-public documents are private by default.

Files must not be directly exposed from a public web directory.

---

# 50. DOCUMENT DOWNLOAD

Download flow:

```text
Authenticate
→ Resolve Tenant
→ Resolve Document Link
→ Authorize Subject
→ Validate Document State
→ Stream / Temporary Controlled Response
→ Audit if Sensitive
```

Knowledge of file path is not authorization.

---

# 51. UPLOAD VALIDATION

Upload checks include:

- extension;
- MIME;
- detected file signature where practical;
- size;
- allowed type;
- tenant context;
- target subject authorization;
- storage path generation.

Client filename is metadata, not trusted storage path.

---

# 52. MALICIOUS FILE DEFENSE

Conceptual document state includes:

```text
PENDING
SCANNING
CLEAN
REJECTED
FAILED
```

Malware scanning becomes mandatory where infrastructure supports it.

Until a file passes required checks, it must not be treated as trusted evidence.

---

# 53. FILE EXECUTION

Uploaded files must never become executable application code.

Upload storage separated from executable application paths.

---

# 54. FILE NAME SECURITY

Storage keys generated server-side.

Reject path traversal patterns.

Original filename may be preserved only as sanitized metadata.

---

# 55. DOCUMENT IMMUTABILITY

Evidence attached to finalized records must not be silently replaced.

Replacement uses:

```text
new document
+ supersedes_document_id
```

where applicable.

---

# 56. PHOTO SECURITY

Asset photos use same private tenant boundary as documents unless explicitly published.

Image metadata must not create unauthorized exposure.

---

# 57. QR TOKEN SECURITY

QR uses opaque high-entropy tokens.

Do not encode:

- sequential asset ID;
- tenant ID;
- sensitive information;

directly as trusted public identity.

---

# 58. QR STORAGE

Prefer storing token hash where practical rather than raw reusable secret.

Token must support:

- rotation;
- revocation;
- expiration where configured.

---

# 59. QR ENUMERATION

Token space must make enumeration impractical.

Public endpoint receives rate limiting and abuse monitoring.

---

# 60. PUBLIC QR PROJECTION

Public QR lookup uses explicit allowlist.

Conceptual safe projection might include:

```text
asset display name
public asset code
general classification
safe status
```

Only fields explicitly approved for public display are returned.

Adding a field to `assets` never automatically publishes it.

---

# 61. PUBLIC QR TENANT SAFETY

Public lookup may identify an asset through opaque token, but must never expose unrelated tenant data.

No public tenant browsing.

---

# 62. QR AUDIT

Security-relevant QR events may record:

- token rotation;
- revocation;
- suspicious abuse;
- protected lookup events where justified.

Avoid excessive personal tracking for ordinary public lookup.

---

# 63. IMPORT SECURITY

Import is an untrusted-data boundary.

Flow:

```text
Upload
→ Parse
→ Map
→ Validate
→ Duplicate Detection
→ Preview
→ Confirm
→ Domain Write
→ Audit
```

No domain writes before confirmation.

---

# 64. IMPORT TENANT RULE

Imported file must never establish authoritative tenant ownership.

Any `tenant_id` column in user input is ignored/rejected.

Target tenant comes from active tenant context.

---

# 65. IMPORT VALIDATION

Each row receives the same domain validation principles as manual entry.

Import is not a bypass around business rules.

---

# 66. SPREADSHEET FORMULA INJECTION

CSV/XLSX exports and imported/exported text require handling of values that could become spreadsheet formulas.

Values beginning with dangerous spreadsheet formula prefixes must be safely encoded where relevant.

---

# 67. IMPORT LIMITS

Apply reasonable limits for:

- file size;
- row count;
- processing time;
- concurrent imports.

Large processing uses queue infrastructure.

---

# 68. EXPORT AUTHORIZATION

Export requires:

```text
permission
+ tenant scope
+ query scope
```

Export must not broaden data visibility beyond the requesting actor's authorization.

---

# 69. EXPORT AUDIT

Sensitive exports are audited with:

- actor;
- tenant;
- export type;
- scope;
- timestamp;
- result.

Do not log exported sensitive contents unnecessarily.

---

# 70. EXPORT STORAGE

Generated export files:

- private;
- temporary where appropriate;
- authorized on download;
- expire according to retention policy.

---

# 71. REPORT SECURITY

Report generation operates within tenant context.

Final report snapshots preserve authorized historical context.

Report URLs do not bypass authorization.

---

# 72. FORMAL DECISION SECURITY

Application approval and formal decision remain distinct.

DESATARA must not fabricate external approval or formal government decision evidence.

Registration of formal decision requires real evidence/reference according to workflow.

---

# 73. SEARCH SECURITY

Search always applies authorization and tenant scope before results are returned.

Search index/cache must not permit cross-tenant discovery.

---

# 74. CACHE SECURITY

Every tenant-dependent cache key must include tenant context.

Conceptual:

```text
tenant:{tenant_uuid}:asset-summary:{...}
```

Forbidden:

```text
asset-summary:{asset_id}
```

when data is tenant-sensitive.

---

# 75. CACHE INVALIDATION

Authorization-sensitive cache must be invalidated when relevant:

- membership changes;
- role changes;
- permission changes;
- authority changes;
- tenant state changes.

Do not cache authorization decisions indefinitely.

---

# 76. QUEUE SECURITY

Every tenant job carries explicit tenant context.

Worker must:

```text
receive job
→ establish tenant context
→ validate tenant state
→ resolve authorized resources
→ execute
→ clear context
```

Worker must never inherit previous job tenant context.

---

# 77. JOB PAYLOAD

Queue payload should contain minimal identifiers.

Avoid embedding unnecessary sensitive data.

Resource must be reloaded authoritatively when executed.

---

# 78. JOB STALE STATE

Queued job must validate current state before mutation.

A job created under an old state/authority must not blindly execute later.

---

# 79. SYSTEM ACTOR

Automated mutations must record:

```text
actor_type = SYSTEM
initiated_by_user_id if applicable
correlation_id
```

System action does not bypass domain rules unless explicitly designed.

---

# 80. NOTIFICATION SECURITY

Notification content follows least disclosure.

Do not put sensitive administrative content into email/WhatsApp unnecessarily.

Prefer:

```text
Anda memiliki permohonan yang memerlukan tindakan.
```

with authenticated link.

---

# 81. NOTIFICATION TENANT ISOLATION

Recipient resolution must use correct tenant/membership context.

Cross-tenant notification is a P0 incident.

---

# 82. RATE LIMITING

Rate limiting applies at minimum to:

- login;
- password reset;
- public QR;
- search where abuse-prone;
- upload;
- import;
- export;
- sensitive actions;
- external APIs.

Limits may differ by endpoint.

---

# 83. BRUTE FORCE PROTECTION

Authentication endpoints require throttling.

Repeated suspicious behavior may trigger:

- additional delay;
- temporary restriction;
- security event.

Avoid permanent denial from trivial spoofable behavior.

---

# 84. API SECURITY

REST API is not the default internal web transport.

When `/api/v1` exists:

- explicit authentication;
- versioning;
- scope/permission;
- tenant isolation;
- validation;
- rate limiting;
- audit where needed.

---

# 85. CORS

CORS must be restrictive.

Do not deploy:

```text
Access-Control-Allow-Origin: *
```

for authenticated sensitive APIs without explicit justification.

---

# 86. HTTPS

Production traffic must use HTTPS.

HTTP should redirect safely to HTTPS where appropriate.

Sensitive cookies require Secure flag.

---

# 87. SECURITY HEADERS

Production baseline should include appropriate:

- Content-Security-Policy;
- X-Content-Type-Options;
- Referrer-Policy;
- frame protection via CSP/frame-ancestors;
- HSTS after HTTPS configuration is verified;
- Permissions-Policy as appropriate.

Exact values defined during deployment hardening.

---

# 88. CSP

CSP should be compatible with Laravel/Inertia/Vue while minimizing unsafe script execution.

Avoid broad `unsafe-inline`/`unsafe-eval` without explicit technical necessity.

---

# 89. CLICKJACKING

DESATARA authenticated pages should not be embeddable by arbitrary third-party sites.

Use CSP `frame-ancestors` or equivalent controls.

---

# 90. SECRET MANAGEMENT

Secrets include:

- APP_KEY;
- database credentials;
- SMTP credentials;
- API keys;
- notification provider secrets;
- backup credentials;
- integration tokens.

Secrets must remain server-side.

---

# 91. SECRET STORAGE

Production secrets must not be committed to Git.

`.env` must not be public.

Frontend build must not receive server secrets.

---

# 92. SECRET ROTATION

Critical credentials should be rotatable without rebuilding domain data.

Secret rotation procedure becomes part of operations documentation.

---

# 93. LOGGING

Application logs must not contain:

- passwords;
- raw session IDs;
- secret tokens;
- private keys;
- full authentication credentials;
- unnecessary sensitive document content.

---

# 94. AUDIT LOG ≠ APPLICATION LOG

Application logs support diagnostics.

Audit logs establish administrative accountability.

They are separate concerns.

---

# 95. AUDIT SECURITY

Audit records are append-oriented.

Ordinary tenant actors cannot edit/delete historical audit entries.

Audit access itself requires permission.

---

# 96. AUDIT EVENTS

Minimum security-sensitive audit events:

```text
Login success/failure where appropriate
Logout
Tenant switch
Support-mode access
Membership change
Role change
Permission change
Official assignment
Authority assignment/revocation
Critical CRUD
Workflow transition
Approval action
Inventory finalization
Reconciliation
Report finalization
Transfer
Disposal
Sensitive document access
Sensitive export
QR rotation/revocation
Tenant configuration change
```

---

# 97. AUDIT SUBJECT

Historical audit reference must not rely on cascading FK.

Store durable subject identity such as:

- subject type;
- subject ID where useful;
- subject UUID;
- snapshot/context.

Deletion of current domain record must not erase audit evidence.

---

# 98. AUDIT INTEGRITY

Application access to audit mutation must be tightly restricted.

Ordinary CRUD endpoints cannot update audit history.

---

# 99. ERROR HANDLING

Production responses must not expose:

- stack trace;
- SQL;
- server path;
- credentials;
- internal configuration.

Use safe user-facing messages + correlation/reference ID.

---

# 100. EXCEPTION LOGGING

Server may log technical context necessary for diagnosis, but sensitive fields must be redacted.

Correlation IDs connect:

```text
user-visible error
→ application log
→ job
→ audit/security event
```

where appropriate.

---

# 101. DEBUG MODE

Production:

```text
APP_DEBUG=false
```

is mandatory.

Debug tooling must not be publicly accessible.

---

# 102. DATABASE SECURITY

Database access uses dedicated application credentials.

Application DB account should receive only privileges required by runtime/migration strategy.

Database must not be directly exposed to public internet unless explicitly secured and justified.

---

# 103. DATABASE INTEGRITY

Security-critical invariants should use DB constraints where feasible:

- tenant composite FK;
- uniqueness;
- check constraints;
- valid date ranges;
- referential integrity;
- immutable-history design.

Application validation alone is insufficient for structural integrity.

---

# 104. DATABASE TRANSACTIONS

Critical multi-write operations use transactions.

Examples:

- approval + action history;
- reconciliation + domain action;
- mutation + current location;
- finalization + snapshot;
- disposal execution;
- transfer execution.

---

# 105. BACKUP SECURITY

Backup scope includes:

- PostgreSQL;
- private documents;
- critical configuration required for restore.

Backups must have:

- access restriction;
- retention;
- integrity checks;
- offsite/redundant strategy where feasible;
- encryption where appropriate.

---

# 106. BACKUP ≠ RECOVERY

A backup is not considered proven until restore has been tested.

---

# 107. RESTORE DRILL

Production readiness requires documented restore procedure and successful restore drill.

Evidence should include:

- backup used;
- restore target;
- duration;
- verification;
- detected issues.

---

# 108. RPO / RTO

Exact RPO/RTO are operational decisions to be locked before production launch.

Until defined, DESATARA cannot claim quantified disaster-recovery capability.

---

# 109. DEPENDENCY SECURITY

Dependencies must be:

- version-controlled;
- reviewed;
- scanned for known vulnerabilities;
- updated intentionally.

Relevant ecosystems include:

- Composer;
- npm.

---

# 110. DEPENDENCY CHANGE

Security-sensitive dependency update requires regression tests.

Do not automatically deploy breaking major updates without review.

---

# 111. BUILD SECURITY

Production build should be reproducible from committed source + locked dependencies.

Do not build production from arbitrary dirty working tree.

---

# 112. CI SECURITY GATE

CI should include:

```text
Dependency install
Formatting/Lint
Static analysis
Frontend checks
Unit tests
Feature tests
Tenant isolation tests
Authorization tests
Workflow tests
Security regression tests
Production build
Migration verification
```

Exact pipeline finalized in Testing & Acceptance Specification.

---

# 113. DEPLOYMENT SECURITY

Deployment requires:

- known commit SHA;
- reviewed configuration;
- production debug disabled;
- secrets present;
- migrations controlled;
- health check;
- rollback path;
- smoke test.

---

# 114. FILE PERMISSIONS

Application runtime should follow least privilege.

Writable paths limited to required runtime/storage locations.

Source/config/secrets must not be unnecessarily writable by web process.

---

# 115. WEB ROOT

Web server document root should expose only intended public application files.

Private storage, `.env`, Git metadata, backup archives, source secrets, and database dumps must not be web-accessible.

---

# 116. SCHEDULED COMMANDS

Scheduled jobs must establish tenant scope explicitly when operating on tenant data.

Global loops over tenants must:

```text
select eligible tenant
→ establish context
→ execute
→ clear context
```

Failure in one tenant must not leak context into another.

---

# 117. OBSERVABILITY

Monitor at minimum:

- application errors;
- queue failures;
- authentication anomalies;
- storage failures;
- backup status;
- scheduled job failures;
- database availability;
- disk capacity.

Security monitoring grows with deployment maturity.

---

# 118. SECURITY EVENT SEVERITY

Conceptual severity:

### P0 / Critical

Examples:

- confirmed cross-tenant leakage;
- authentication bypass;
- arbitrary unauthorized approval;
- secret/private-key exposure;
- destructive integrity compromise.

### P1 / High

Examples:

- significant privilege escalation;
- private document unauthorized access;
- workflow bypass;
- backup unable to restore.

### P2 / Medium

Examples:

- limited abuse weakness;
- missing hardening;
- low-impact information disclosure.

Exact incident classification may be refined operationally.

---

# 119. INCIDENT RESPONSE

Minimum process:

```text
Detect
→ Contain
→ Preserve Evidence
→ Assess Scope
→ Remediate
→ Verify
→ Restore if required
→ Document
→ Prevent Regression
```

Do not destroy relevant logs/evidence during investigation.

---

# 120. CROSS-TENANT INCIDENT

Suspected cross-tenant leakage receives immediate priority.

Actions may include:

- disable affected path;
- revoke exposed token;
- suspend compromised credential;
- preserve logs;
- identify tenants/resources involved;
- patch;
- regression test;
- redeploy safely.

---

# 121. SECURITY TESTING STRATEGY

Security is tested at:

```text
Unit
Feature
Integration
Database
HTTP
Browser/E2E
Deployment/Configuration
```

No single test layer is sufficient.

---

# 122. P0 TENANT ISOLATION TEST MATRIX

For Tenant A and Tenant B verify:

```text
Asset
Document
Photo
Location
Mutation
Maintenance
Inventory
Discrepancy
Reconciliation
Approval
Authority
Report
Export
Import
Audit
Notification
Search
QR
Queue
Cache
```

Tenant A must not access Tenant B through:

```text
GET
POST
PUT/PATCH
DELETE where applicable
direct UUID
modified route
modified form
search
download
export
background job
cached response
```

---

# 123. P0 IDOR TEST

For every tenant-owned resource:

```text
authorized resource UUID
→ replace with other-tenant UUID
→ request
→ deny safely
→ no metadata leak
```

---

# 124. P0 ATTACHMENT TEST

Verify:

- guessed document ID denied;
- guessed UUID denied;
- copied download URL denied after context change where required;
- other-tenant file denied;
- direct storage path unavailable;
- revoked/invalid evidence unavailable according to policy.

---

# 125. P0 APPROVAL BYPASS TEST

Attempt:

- approve without permission;
- approve without authority;
- approve other tenant;
- self-approve where forbidden;
- approve wrong workflow state;
- approve stale version;
- replay approval;
- fabricate external approval.

All must fail safely.

---

# 126. P0 TENANT_ID TAMPERING TEST

Inject another tenant ID into:

- form;
- JSON;
- query;
- route;
- import;
- hidden field.

Ownership must remain active tenant or request must be rejected.

---

# 127. P0 CACHE ISOLATION TEST

Populate cache using Tenant A.

Request equivalent data as Tenant B.

Tenant B must never receive Tenant A payload.

---

# 128. P0 QUEUE ISOLATION TEST

Queue jobs for multiple tenants.

Verify each job:

- restores correct tenant;
- reads correct resource;
- writes correct tenant;
- clears tenant context;
- cannot reuse previous job context.

---

# 129. P0 NOTIFICATION TEST

Trigger equivalent notifications for multiple tenants.

Verify recipients and payload belong only to correct tenant.

---

# 130. P0 INERTIA PROP TEST

Unauthorized data must not exist in serialized props even when UI does not render it.

Inspect raw response payload.

---

# 131. P0 SUPPORT MODE TEST

Attempt cross-tenant platform access:

```text
without support grant
expired grant
revoked grant
wrong tenant
wrong scope
```

All denied.

Valid grant must be time/scope limited and audited.

---

# 132. P0 FINALIZATION TEST

Attempt finalization:

- without permission;
- without authority;
- with unresolved blocker;
- with stale version;
- twice;
- from wrong tenant.

No invalid final state may be created.

---

# 133. P0 PUBLIC QR TEST

Verify:

- random invalid token safe;
- revoked token safe;
- other data cannot be enumerated;
- only allowlisted fields returned;
- private document links absent;
- tenant internals absent;
- rate limiting works.

---

# 134. P1 WEB SECURITY TESTS

Include regression coverage for:

- CSRF;
- stored XSS;
- reflected XSS;
- SQL injection attempts;
- mass assignment;
- unsafe redirect;
- upload bypass;
- MIME spoofing;
- path traversal;
- oversized upload;
- brute force/rate limits;
- session fixation;
- stale state;
- duplicate action.

---

# 135. SECURITY TEST DATA

Tests require at least:

```text
Tenant A
Tenant B

User A
User B

Multiple memberships
Multiple roles
Valid authority
Expired authority
No authority
Suspended tenant
Valid support grant
Expired support grant
```

Without multi-tenant fixtures, tenant isolation cannot be proven.

---

# 136. RELEASE BLOCKERS

Production release is blocked by any unresolved:

### P0

- cross-tenant leakage;
- auth bypass;
- authorization bypass;
- authority bypass on critical action;
- unauthorized private document access;
- unsafe approval/finalization;
- tenant cache/job leak;
- public QR sensitive leak;
- exposed production secret;
- destructive data integrity flaw.

### P1

must be explicitly resolved or accepted through documented security risk process before production.

No silent acceptance.

---

# 137. SECURITY ACCEPTANCE EVIDENCE

"Secure" cannot be claimed from code review alone.

Evidence should include:

```text
Code
Configuration
Automated Tests
Test Output
Deployment State
Dependency Scan
Backup Evidence
Restore Evidence
Smoke Test
```

---

# 138. PRODUCTION SECURITY CHECKLIST

Before launch:

```text
[ ] HTTPS enforced
[ ] APP_DEBUG=false
[ ] Production secrets protected
[ ] Database not publicly exposed
[ ] Private storage verified
[ ] Tenant isolation tests PASS
[ ] Authorization tests PASS
[ ] Authority tests PASS
[ ] Upload tests PASS
[ ] QR/public tests PASS
[ ] Queue isolation tests PASS
[ ] Cache isolation tests PASS
[ ] Dependency scan reviewed
[ ] Rate limits enabled
[ ] Security headers reviewed
[ ] Audit events verified
[ ] Backup successful
[ ] Restore drill successful
[ ] Monitoring enabled
[ ] Rollback path tested
[ ] Production smoke test PASS
```

---

# 139. SECURITY ANTI-PATTERNS — FORBIDDEN

```text
Trusting hidden form fields
Trusting tenant_id from client
Role-only authorization for regulatory actions
Vue-only authorization
Global resource lookup before tenant scope
Public storage for private evidence
Guessable public QR identity
Generic status mutation
Direct edit of finalized records
Shared tenant cache without tenant key
Queue job without tenant context
Hardcoded platform-admin tenant bypass
Secrets committed to repository
APP_DEBUG=true in production
Ignoring failed backup
Calling untested backup "recoverable"
```

---

# 140. IMPLEMENTATION SECURITY ORDER

Security implementation should follow:

```text
1. Authentication baseline
2. Tenant context
3. Membership enforcement
4. RBAC/Policy
5. Authority enforcement
6. Tenant-aware persistence
7. Validation
8. Workflow command boundary
9. Private storage
10. Audit
11. QR/public hardening
12. Import/export hardening
13. Queue/cache isolation
14. Rate limiting
15. Deployment hardening
16. Backup/restore
17. Security regression suite
```

Security cannot be postponed until after feature implementation.

---

# 141. SECURITY CHANGE CONTROL

Changes affecting:

- tenant boundary;
- authentication;
- RBAC;
- authority;
- workflow;
- public lookup;
- documents;
- encryption/secrets;
- audit;
- backup;

require security review and regression tests.

---

# 142. SOURCE OF TRUTH

Conflict resolution:

**Applicable Regulation  
→ RTM  
→ Master Blueprint  
→ PRD  
→ Workflow  
→ RBAC & Authority  
→ ERD  
→ Data Dictionary  
→ Security Specification  
→ Testing Specification  
→ Implementation**

Security implementation may strengthen a requirement.

It must not silently weaken locked business/regulatory invariants.

---

# 143. SECURITY CONTRACT

The following are non-negotiable:

> **Tenant isolation is P0.**

> **Authorization is server-side.**

> **Permission is not regulatory authority.**

> **Client data is untrusted.**

> **Private evidence remains private by default.**

> **Critical workflow transitions cannot be performed through generic status updates.**

> **Historical/finalized administrative evidence cannot be silently rewritten.**

> **Background jobs, cache, search, exports, notifications, and public surfaces are part of the security boundary.**

> **Backup is not proven until restore succeeds.**

> **A security claim requires executable evidence, not assumption.**

---

# 144. DEFINITION OF SECURITY READY

DESATARA may be considered **Security Ready for Production** only when:

1. P0 security tests pass;
2. tenant isolation is verified;
3. authentication/session hardening is verified;
4. permission and authority enforcement are verified;
5. private file access is verified;
6. QR/public exposure is verified;
7. import/export controls are verified;
8. queue/cache tenant isolation is verified;
9. production configuration is hardened;
10. dependencies are reviewed;
11. audit/security events are operational;
12. backup exists;
13. restore drill succeeds;
14. monitoring is operational;
15. no unresolved P0 remains;
16. unresolved P1 risks, if any, have explicit documented disposition.

---

# 145. STATUS

**SECURITY SPECIFICATION DESATARA v1.0 — LOCKED**

This document is the canonical security baseline for DESATARA implementation and production-readiness verification.