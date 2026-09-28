# BUSINESS PROCESS & WORKFLOW SPECIFICATION — DESATARA

## Platform Pengelolaan Aset Desa

**Document:** `docs/WORKFLOWS.md`  
**Version:** 1.0  
**Status:** LOCKED  
**Parent:** PRD DESATARA v1.0  
**Architecture:** Master Blueprint DESATARA v3.0  
**Regulatory Baseline:** Regulatory Traceability Matrix v1.0  
**Product Model:** Multi-Desa / Multi-Tenant

---

# 1. PURPOSE

Dokumen ini mendefinisikan bagaimana proses bisnis DESATARA berjalan dari awal sampai akhir.

Dokumen mengunci:

- workflow;
- state;
- transition;
- actor;
- authority gate;
- validation gate;
- evidence requirement;
- approval;
- reconciliation;
- finalization;
- cancellation;
- revision;
- audit behavior;
- concurrency behavior.

Workflow Specification menjadi penghubung:

**PRD → Workflow → Authority → Data → UI → Test**

---

# 2. WORKFLOW PRINCIPLES

Semua workflow DESATARA mengikuti prinsip berikut.

## WF-PR-001 — Server Authority

Laravel merupakan authoritative workflow engine.

Vue hanya mengirim intent.

Client tidak menentukan:

- permission;
- authority;
- tenant;
- valid transition;
- final approval;
- regulatory state.

---

## WF-PR-002 — Tenant Isolation

Semua tenant-owned workflow berjalan dalam active tenant context.

Setiap transition wajib memverifikasi:

**Actor → Membership → Tenant → Permission → Resource**

---

## WF-PR-003 — Explicit Transition

Critical state tidak boleh berubah melalui generic update.

Contoh yang dilarang:

`PATCH asset { status: "disposed" }`

Perubahan harus melalui explicit action:

`executeDisposal()`

atau equivalent application action.

---

## WF-PR-004 — Historical Integrity

Transition tidak boleh menghapus historical state.

History dipertahankan melalui:

- transaction record;
- workflow action;
- audit;
- snapshot;
- revision.

---

## WF-PR-005 — Evidence

Jika business/regulatory rule mensyaratkan evidence, transition tidak boleh selesai tanpa evidence.

---

## WF-PR-006 — Authority

Permission teknis tidak otomatis berarti kewenangan regulatif.

Critical transition dapat membutuhkan:

**Permission + Active Authority + Evidence + Valid State**

---

## WF-PR-007 — Version Preservation

Workflow instance mempertahankan workflow version yang digunakan saat dimulai.

Perubahan workflow definition tidak mengubah historical workflow.

---

## WF-PR-008 — Idempotency

Critical action harus aman terhadap:

- double-click;
- duplicate request;
- retry;
- queue retry;
- repeated callback.

---

## WF-PR-009 — Concurrency

Critical transition menggunakan transaction/locking/concurrency control bila diperlukan.

---

## WF-PR-010 — Auditability

Critical transition menghasilkan audit event.

---

# 3. STANDARD WORKFLOW STATE MODEL

Workflow reusable menggunakan state conceptual:

`DRAFT`

↓

`SUBMITTED`

↓

`UNDER_REVIEW`

↓

`APPROVED`

↓

`EXECUTED`

↓

`FINALIZED`

Tidak semua workflow menggunakan seluruh state.

Alternative states:

- `REVISION_REQUESTED`
- `REJECTED`
- `CANCELLED`
- `EXPIRED`

---

# 4. STANDARD ACTION MODEL

Canonical workflow actions:

- create;
- save_draft;
- submit;
- verify;
- request_revision;
- resubmit;
- approve;
- reject;
- cancel;
- execute;
- reconcile;
- finalize;
- reopen — hanya jika workflow explicitly memperbolehkan;
- archive.

Tidak ada arbitrary state mutation.

---

# 5. STANDARD TRANSITION GUARD

Setiap critical transition menjalankan:

**AUTHENTICATE**

→ **RESOLVE ACTIVE TENANT**

→ **VERIFY MEMBERSHIP**

→ **LOAD RESOURCE IN TENANT**

→ **AUTHORIZE PERMISSION**

→ **VERIFY AUTHORITY**

→ **VERIFY CURRENT STATE**

→ **VALIDATE BUSINESS RULE**

→ **VALIDATE REQUIRED EVIDENCE**

→ **LOCK IF REQUIRED**

→ **EXECUTE TRANSACTION**

→ **WRITE AUDIT**

→ **DISPATCH SAFE SIDE EFFECT**

---

# 6. ACTOR MODEL

Canonical actor classes:

### Platform Admin
Platform-level administration.

### Tenant Admin
Tenant technical administration.

### Kepala Desa
Regulatory/organizational authority sesuai context yang berlaku.

### Sekretaris Desa
Administrative/regulatory role sesuai authority assignment.

### Pengurus Aset
Operational asset administration.

### BPD / Monitoring
Read/monitoring sesuai scope.

### Auditor / Pemeriksa
Authorized inspection/read access.

### Viewer
Read-only limited access.

Role tidak otomatis menentukan legal authority.

---

# 7. TENANT ONBOARDING

## WF-TEN-001

Flow:

`TENANT REQUESTED`

→ `VALIDATION`

→ `PROVISIONED`

→ `ACTIVE`

Possible alternate:

`PENDING → REJECTED`

Lifecycle:

`ACTIVE → SUSPENDED → ACTIVE`

atau:

`ACTIVE/SUSPENDED → ARCHIVED`

---

## Provisioning Result

Tenant provisioning membuat foundation:

- tenant identity;
- tenant settings;
- initial membership;
- tenant administrator;
- reference initialization;
- audit event.

---

## Guard

Hanya authorized platform operation yang dapat mengubah tenant lifecycle.

Tenant Admin tidak dapat mengaktifkan tenant yang suspended oleh platform authority.

---

# 8. USER MEMBERSHIP WORKFLOW

## WF-IAM-001

Flow:

`USER IDENTIFIED`

→ `MEMBERSHIP CREATED`

→ `ROLE ASSIGNED`

→ `ACTIVE`

Membership dapat:

- suspended;
- expired;
- revoked.

User account tidak dihapus hanya karena membership berakhir.

---

# 9. ROLE ASSIGNMENT

## WF-IAM-002

Flow:

`SELECT MEMBERSHIP`

→ `SELECT ROLE`

→ `VALIDATE ASSIGNER`

→ `VALIDATE TENANT`

→ `ASSIGN`

→ `AUDIT`

Role assignment tidak boleh memberikan privilege melebihi authority assigner.

Privilege escalation test wajib tersedia.

---

# 10. OFFICIAL / AUTHORITY ASSIGNMENT

## WF-AUT-001

Flow:

`CREATE ASSIGNMENT`

→ `ATTACH APPOINTMENT REFERENCE`

→ `SET VALID PERIOD`

→ `VERIFY`

→ `ACTIVATE`

Possible:

`ACTIVE → EXPIRED`

`ACTIVE → REVOKED`

`ACTIVE → SUPERSEDED`

---

## Authority Validation

Critical action harus memeriksa authority pada **waktu tindakan**, bukan hanya current user role.

---

# 11. ASSET REGISTRATION

## WF-AST-001

Flow:

`DRAFT`

→ `VALIDATION`

→ `READY`

→ `REGISTERED`

---

## Required Validation

Minimal:

- tenant;
- asset identity;
- classification;
- acquisition information minimum;
- quantity;
- value where applicable;
- location;
- condition;
- duplicate check.

---

## Duplicate Detection

Potential duplicate tidak otomatis digabung.

System:

`DETECT → WARN → REVIEW → USER DECISION`

Decision harus auditable untuk high-risk duplicate.

---

# 12. ASSET UPDATE

## WF-AST-002

Normal editable metadata dapat diperbarui melalui controlled update.

Tetapi field yang merupakan lifecycle result tidak boleh arbitrary edit.

Contoh:

- current location;
- disposed state;
- formal usage;
- transfer result.

Field tersebut berubah melalui domain workflow terkait.

---

# 13. ASSET CORRECTION

## WF-AST-003

Kesalahan administratif dapat dikoreksi.

Flow:

`CORRECTION REQUEST`

→ `VALIDATE`

→ `AUTHORIZE`

→ `APPLY`

→ `AUDIT`

Untuk material correction, sistem menyimpan before/after.

---

# 14. DOCUMENT UPLOAD

## WF-DOC-001

Flow:

`SELECT CONTEXT`

→ `UPLOAD`

→ `VALIDATE`

→ `STORE PRIVATE`

→ `CREATE METADATA`

→ `LINK`

→ `AUDIT`

---

## Validation

Periksa:

- tenant;
- permission;
- MIME;
- extension;
- size;
- file signature jika tersedia;
- target resource;
- ownership.

---

# 15. DOCUMENT DOWNLOAD

## WF-DOC-002

Flow:

`REQUEST`

→ `AUTH`

→ `TENANT CHECK`

→ `POLICY`

→ `RESOURCE CHECK`

→ `AUTHORIZED FILE RESPONSE`

Direct public path tidak digunakan untuk private evidence.

---

# 16. QR ISSUANCE

## WF-QR-001

Flow:

`ASSET REGISTERED`

→ `GENERATE OPAQUE TOKEN`

→ `ISSUE QR`

→ `ACTIVE`

QR dapat:

`ACTIVE → ROTATED`

atau:

`ACTIVE → REVOKED`

Old token tidak boleh tetap memberikan akses setelah revoke.

---

# 17. LOCATION MUTATION

## WF-MUT-001

Flow:

`DRAFT`

→ `SUBMITTED`

→ `VALIDATED`

→ `APPROVED` jika diperlukan

→ `EXECUTED`

→ `COMPLETED`

---

## Execution Transaction

Dalam satu logical transaction:

1. lock relevant asset;
2. validate current location;
3. create mutation history;
4. update current location;
5. record actor;
6. write audit.

Jika salah satu gagal:

**ROLLBACK**

---

# 18. RESPONSIBILITY MUTATION

## WF-MUT-002

Perubahan penanggung jawab menggunakan transaction/history, bukan overwrite tanpa jejak.

Flow mengikuti mutation workflow.

---

# 19. MAINTENANCE

## WF-MNT-001

Flow:

`PLANNED`

→ `IN_PROGRESS`

→ `COMPLETED`

Possible:

`PLANNED → CANCELLED`

---

## Completion

Completion dapat merekam:

- completion date;
- actual cost;
- vendor;
- condition before;
- condition after;
- evidence.

Maintenance tidak otomatis mengubah asset condition kecuali explicit authorized update dilakukan sebagai bagian transaction.

---

# 20. PHYSICAL INVENTORY SESSION

## WF-INV-001

Flow:

`DRAFT`

→ `PREPARED`

→ `IN_PROGRESS`

→ `REVIEW`

→ `RECONCILIATION`

→ `FINALIZED`

Possible:

`DRAFT/PREPARED → CANCELLED`

---

# 21. INVENTORY PREPARATION

## WF-INV-002

Preparation menentukan:

- tenant;
- scope;
- locations;
- asset classes;
- period;
- team;
- expected assets.

Expected dataset di-freeze/snapshot secara memadai agar hasil tetap dapat diinterpretasikan.

---

# 22. INVENTORY VERIFICATION

## WF-INV-003

Untuk setiap expected asset:

`NOT_CHECKED`

→ `CHECKED`

Result:

- matched;
- missing;
- relocated;
- condition mismatch;
- duplicate suspected.

Field evidence:

- scan;
- photo;
- notes;
- observed location;
- observed condition;
- verifier;
- timestamp.

---

# 23. NEWLY DISCOVERED ASSET

## WF-INV-004

Barang yang ditemukan tetapi tidak cocok dengan master:

`DISCOVERED`

→ `REVIEW`

→ salah satu:

`LINK_EXISTING`

`REGISTER_NEW`

`MARK_UNIDENTIFIED`

`DUPLICATE_SUSPECTED`

Inventory scanner tidak otomatis membuat authoritative asset master.

---

# 24. INVENTORY DISCREPANCY

## WF-INV-005

Flow:

`OPEN`

→ `REVIEWED`

→ `RESOLUTION_PROPOSED`

→ `RECONCILED`

Possible:

`OPEN → INVALIDATED`

---

# 25. INVENTORY RECONCILIATION

## WF-INV-006

Reconciliation dapat menghasilkan:

- no change;
- asset correction;
- location mutation;
- condition update;
- new asset registration;
- duplicate investigation;
- further administrative action.

Setiap master change menggunakan corresponding domain action.

Tidak ada generic “apply inventory to master”.

---

# 26. INVENTORY FINALIZATION

## WF-INV-007

Session hanya dapat finalized jika:

- required assets processed;
- blocking discrepancies resolved atau formally accepted;
- required evidence tersedia;
- reviewer authorized.

Finalization menghasilkan immutable inventory summary/snapshot.

---

# 27. GENERIC APPROVAL REQUEST

## WF-APR-001

Flow:

`DRAFT`

→ `SUBMITTED`

→ `STEP 1`

→ `STEP N`

→ `APPROVED`

Possible:

- revision;
- rejection;
- cancellation.

---

# 28. APPROVAL STEP

## WF-APR-002

Untuk setiap step:

`PENDING`

→ `APPROVED`

atau:

`PENDING → REJECTED`

atau:

`PENDING → REVISION_REQUESTED`

Action menyimpan:

- actor;
- authority snapshot;
- action;
- timestamp;
- notes;
- evidence;
- workflow version.

---

# 29. REVISION

## WF-APR-003

Flow:

`REVISION_REQUESTED`

→ `EDIT`

→ `RESUBMIT`

→ kembali ke step yang ditentukan workflow definition.

Historical approval action tidak dihapus.

---

# 30. REJECTION

## WF-APR-004

Rejection membutuhkan:

- authorized actor;
- valid state;
- reason.

Rejected request tidak boleh diubah menjadi approved melalui direct database/state edit.

Jika business process memperbolehkan pengajuan ulang, dibuat melalui explicit resubmission/new request rule.

---

# 31. CANCELLATION

## WF-APR-005

Cancellation hanya tersedia jika workflow definition memperbolehkan.

Cancellation setelah formal execution/finalization umumnya tidak diperbolehkan.

Gunakan corrective/reversal process jika diperlukan.

---

# 32. USAGE DETERMINATION

## WF-USG-001

Conceptual flow:

`DRAFT`

→ `ASSET SELECTION`

→ `VALIDATION`

→ `REVIEW`

→ `DECISION REFERENCE`

→ `FINALIZED`

Finalized determination mempertahankan:

- period;
- included assets;
- decision evidence;
- authority;
- workflow version.

---

# 33. UTILIZATION

## WF-UTL-001

Conceptual flow:

`REQUEST`

→ `ELIGIBILITY REVIEW`

→ `VALUATION/REVIEW IF REQUIRED`

→ `INTERNAL APPROVAL`

→ `EXTERNAL APPROVAL IF REQUIRED`

→ `FORMAL DECISION/AGREEMENT`

→ `ACTIVE`

→ `COMPLETED`

atau:

`TERMINATED`

Detail rules mengikuti regulatory specification terkait.

---

# 34. SAFEGUARDING

## WF-SFG-001

Flow:

`ASSESS`

→ `IDENTIFY GAP`

→ `ACTION PLAN`

→ `IMPLEMENT`

→ `VERIFY`

→ `CLOSE`

Safeguarding finding tidak otomatis menjadi legal finding.

---

# 35. VALUATION

## WF-VAL-001

Flow:

`REQUEST`

→ `DEFINE PURPOSE`

→ `ASSIGN/REFERENCE AUTHORIZED VALUER`

→ `VALUATION`

→ `EVIDENCE`

→ `REVIEW`

→ `FINALIZED`

Historical valuation tidak overwrite.

---

# 36. TRANSFER

## WF-TRF-001

Generic flow:

`REQUEST`

→ `CLASSIFY ASSET`

→ `DETERMINE TRANSFER TYPE`

→ `ELIGIBILITY CHECK`

→ `VALUATION IF REQUIRED`

→ `INTERNAL REVIEW`

→ `EXTERNAL APPROVAL IF REQUIRED`

→ `FORMAL DECISION`

→ `EXECUTION`

→ `COMPLETED`

---

## Specialized Branching

Workflow definition dapat bercabang berdasarkan:

- transfer type;
- land/non-land;
- building/non-building;
- strategic classification;
- regulatory authority.

---

# 37. DISPOSAL

## WF-DSP-001

Flow:

`REQUEST`

→ `VALIDATION`

→ `ASSET CLASSIFICATION`

→ `REVIEW`

→ `APPROVAL`

→ `DOCUMENT PREPARATION`

→ `FORMAL DECISION`

→ `EXECUTION`

→ `DISPOSED`

---

## Disposal Guard

`ACTIVE → DISPOSED`

melalui generic asset edit:

**FORBIDDEN**

---

# 38. REPORT GENERATION

## WF-RPT-001

Flow:

`SELECT PERIOD`

→ `SELECT TEMPLATE VERSION`

→ `VALIDATE DATA`

→ `GENERATE DRAFT`

→ `REVIEW`

→ `FINALIZE`

→ `SNAPSHOT`

→ `EXPORT/ARCHIVE`

---

# 39. REPORT FINALIZATION

## WF-RPT-002

Finalization:

1. lock report;
2. verify state;
3. verify authority;
4. verify required data;
5. resolve template version;
6. resolve regulatory context;
7. resolve official/signatory context;
8. generate immutable snapshot;
9. compute integrity metadata/hash where implemented;
10. mark finalized;
11. audit.

Concurrent finalization harus menghasilkan satu authoritative finalized result.

---

# 40. REPORT REVISION

## WF-RPT-003

Finalized report tidak diedit.

Correction:

`FINALIZED REPORT`

→ `CREATE REVISION`

→ `DRAFT REVISION`

→ `REVIEW`

→ `FINALIZE REVISION`

Previous snapshot tetap tersedia.

---

# 41. IMPORT

## WF-IMP-001

Flow:

`UPLOAD`

→ `PARSE`

→ `COLUMN MAPPING`

→ `VALIDATION`

→ `PREVIEW`

→ `DUPLICATE DETECTION`

→ `CONFIRM`

→ `IMPORT`

→ `SUMMARY`

---

## Import Guard

Import tidak boleh:

- bypass tenant scope;
- bypass validation;
- bypass permission;
- silently replace historical data;
- trust tenant_id dari uploaded file;
- automatically approve regulatory transaction.

---

# 42. EXPORT

## WF-EXP-001

Flow:

`REQUEST`

→ `AUTHORIZE`

→ `TENANT SCOPE`

→ `FILTER`

→ `GENERATE`

→ `AUDIT`

→ `DOWNLOAD`

Sensitive export dapat memerlukan additional audit/permission.

---

# 43. INTEROPERABILITY EXPORT

## WF-INT-001

Flow:

`NOT_PREPARED`

→ `VALIDATE`

→ `READY_FOR_EXPORT`

→ `EXPORT`

→ `EXPORTED`

→ `RECONCILIATION`

→ `RECONCILED`

`SYNCED` tidak digunakan tanpa actual supported synchronization.

---

# 44. PUBLIC QR LOOKUP

## WF-PUB-001

Flow:

`TOKEN`

→ `VALIDATE TOKEN`

→ `VERIFY ACTIVE`

→ `LOAD ALLOWLIST DATA`

→ `PUBLIC RESPONSE`

Tidak boleh melakukan generic model serialization.

---

# 45. BACKGROUND JOB

## WF-SYS-001

Flow:

`DISPATCH`

→ `SERIALIZE TENANT/RESOURCE CONTEXT`

→ `QUEUE`

→ `WORKER LOAD`

→ `REVALIDATE`

→ `EXECUTE`

→ `AUDIT IF REQUIRED`

→ `COMPLETE`

Retry harus idempotent untuk critical side effects.

---

# 46. NOTIFICATION

## WF-NOT-001

Flow:

`DOMAIN EVENT`

→ `RESOLVE TENANT`

→ `RESOLVE RECIPIENT`

→ `AUTHORIZE VISIBILITY`

→ `QUEUE`

→ `DELIVER`

Notification payload tidak boleh mengungkap resource yang tidak dapat diakses recipient.

---

# 47. TENANT SWITCH

## WF-TEN-002

Flow:

`REQUEST SWITCH`

→ `VERIFY MEMBERSHIP`

→ `SET ACTIVE TENANT`

→ `CLEAR TENANT-SENSITIVE STATE`

→ `LOAD NEW CONTEXT`

Server tetap memverifikasi tenant pada setiap subsequent request.

---

# 48. SECURITY INCIDENT

## WF-SEC-001

Jika terdeteksi suspected cross-tenant leakage atau authorization bypass:

`DETECT`

→ `CONTAIN`

→ `LOG`

→ `ASSESS`

→ `FIX`

→ `REGRESSION TEST`

→ `VERIFY`

→ `CLOSE`

Cross-tenant leakage merupakan P0.

---

# 49. WORKFLOW DEFINITION VERSIONING

Workflow definition memiliki immutable published version.

Lifecycle:

`DRAFT VERSION`

→ `REVIEW`

→ `PUBLISHED`

→ `SUPERSEDED`

Published version tidak diedit secara destruktif.

Perubahan membuat version baru.

Existing workflow instance tetap menunjuk version awal.

---

# 50. REGULATORY RULE CHANGE

## WF-REG-001

Flow:

`IDENTIFY NEW/AMENDED RULE`

→ `REGISTER SOURCE`

→ `ANALYZE IMPACT`

→ `UPDATE TRACEABILITY`

→ `CREATE RULE VERSION`

→ `UPDATE WORKFLOW/TEMPLATE IF REQUIRED`

→ `TEST`

→ `ACTIVATE EFFECTIVE VERSION`

→ `SUPERSEDE OLD VERSION`

Historical records tetap menggunakan historical context.

---

# 51. INVALID TRANSITION RESPONSE

Jika transition invalid:

Server:

- menolak request;
- tidak melakukan partial mutation;
- mengembalikan controlled error;
- dapat mencatat security/audit event jika relevan.

UI menampilkan actionable error.

---

# 52. STALE STATE

Jika user membuka data lama dan state berubah sebelum submit:

Server tidak menerima stale transition.

Response harus meminta:

**refresh → review latest state → retry**

Critical workflow tidak menggunakan blind last-write-wins.

---

# 53. TRANSACTION BOUNDARY

Transaction wajib dipertimbangkan pada:

- mutation execution;
- inventory reconciliation;
- approval;
- finalization;
- register allocation;
- transfer execution;
- disposal execution;
- critical import;
- authority-sensitive action.

Side effect eksternal idealnya dilakukan setelah authoritative DB commit.

---

# 54. AUDIT EVENT REQUIREMENT

Critical event mencakup minimal:

- tenant;
- actor;
- action;
- subject;
- transition;
- before state;
- after state;
- authority context jika relevan;
- workflow version;
- timestamp;
- request/correlation ID.

---

# 55. WORKFLOW ERROR CLASSES

Canonical error families:

### Authorization Error
Actor tidak mempunyai permission.

### Authority Error
Actor mempunyai permission teknis tetapi tidak mempunyai authority yang diperlukan.

### Tenant Error
Resource bukan milik active tenant.

### State Error
Transition tidak valid.

### Validation Error
Input tidak memenuhi requirement.

### Evidence Error
Required evidence belum tersedia.

### Conflict Error
State telah berubah/concurrent action terjadi.

### Regulatory Error
Current regulatory rule tidak mengizinkan transition.

---

# 56. UI WORKFLOW REQUIREMENTS

Vue UI harus menampilkan:

- current state;
- allowed actions;
- blocking requirement;
- pending approval;
- revision reason;
- validation error;
- evidence status;
- history;
- loading state;
- conflict state.

UI tidak boleh menampilkan action sebagai enabled hanya berdasarkan hardcoded role.

Allowed action berasal dari server authorization/context.

---

# 57. WORKFLOW TEST CONTRACT

Setiap critical workflow minimal mempunyai test:

### Happy Path
Valid transition berhasil.

### Permission Failure
User tanpa permission ditolak.

### Authority Failure
Permission ada tetapi authority tidak valid.

### Tenant Failure
Cross-tenant resource ditolak.

### Invalid State
Transition dari state salah ditolak.

### Missing Evidence
Required evidence tidak ada.

### Duplicate Action
Repeated request aman.

### Concurrency
Concurrent transition tidak merusak state.

### Audit
Successful critical transition menghasilkan audit.

### Historical Integrity
History sebelumnya tetap tersedia.

---

# 58. P0 WORKFLOW TESTS

Wajib sebelum production:

- cross-tenant asset mutation;
- cross-tenant approval;
- cross-tenant document;
- unauthorized disposal;
- unauthorized transfer;
- unauthorized report finalization;
- duplicate approval;
- double report finalization;
- inventory overwrite bypass;
- stale approval;
- tenant switch leakage;
- queue tenant leakage;
- invalid public QR disclosure.

---

# 59. WORKFLOW → ERD CONTRACT

ERD berikutnya harus mampu merepresentasikan:

- workflow instance;
- workflow definition/version;
- state;
- action history;
- authority snapshot;
- evidence;
- approval;
- external approval;
- actor;
- tenant;
- regulatory context;
- timestamps;
- finalization;
- revision;
- cancellation;
- concurrency metadata bila diperlukan.

ERD tidak boleh menyederhanakan workflow sehingga historical meaning hilang.

---

# 60. WORKFLOW → RBAC CONTRACT

RBAC Matrix berikutnya harus menentukan untuk setiap action:

**WHO CAN REQUEST?**

**WHO CAN VIEW?**

**WHO CAN VERIFY?**

**WHO CAN APPROVE?**

**WHO CAN EXECUTE?**

**WHO CAN FINALIZE?**

**WHICH ACTION REQUIRES REGULATORY AUTHORITY?**

Role saja tidak cukup untuk menjawab seluruh pertanyaan tersebut.

---

# 61. WORKFLOW → UI CONTRACT

Setiap screen workflow nantinya harus mempunyai:

- state indicator;
- primary action;
- secondary action;
- disabled-state explanation;
- validation;
- evidence area;
- approval history;
- audit/history link;
- confirmation untuk destructive/irreversible action;
- responsive behavior.

---

# 62. WORKFLOW → REGULATORY TRACEABILITY

Workflow regulatif harus mempunyai trace:

**WF-ID**

→ **BR-ID**

→ **RTM-ID**

→ **Regulation Provision**

Workflow tanpa regulatory source tidak boleh diklaim sebagai regulatory requirement.

---

# 63. OPEN WORKFLOW ITEMS

Masih membutuhkan keputusan/verification:

- exact approval chain berdasarkan asset/process;
- regional authority overlay;
- exact formal decision requirements;
- asset coding process;
- report signing flow;
- document numbering;
- detailed land transfer branches;
- detailed disposal branches;
- interoperability process dengan aplikasi resmi;
- retention/archive workflow.

Item ini harus diselesaikan melalui Regulatory Matrix/RBAC/implementation specification, bukan hidden assumption developer.

---

# 64. DEFINITION OF WORKFLOW LOCKED

Workflow Specification dapat diubah menjadi **LOCKED** setelah:

- state model disetujui;
- critical transition disetujui;
- actor boundary disetujui;
- authority distinction disetujui;
- evidence rules disetujui;
- concurrency rules disetujui;
- historical integrity rules disetujui;
- inventory reconciliation disetujui;
- approval/finalization behavior disetujui;
- unresolved regulatory items tercatat.

---

# 65. FINAL WORKFLOW INVARIANT

Setiap critical workflow DESATARA harus dapat menjawab:

> **Siapa melakukan apa, pada tenant mana, terhadap resource apa, dari state apa ke state apa, dengan permission apa, berdasarkan kewenangan apa, memakai workflow/rule versi berapa, dengan evidence apa, kapan dilakukan, dan bagaimana sejarahnya dapat dibuktikan kembali?**

Jika pertanyaan tersebut tidak dapat dijawab, workflow belum memenuhi baseline DESATARA.

---

# 66. STATUS

**Business Process & Workflow Specification DESATARA v1.0 — LOCKED**

Dokumen berikutnya:

**RBAC & Regulatory Authority Matrix DESATARA v1.0**