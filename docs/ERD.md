# ENTITY RELATIONSHIP DESIGN — DESATARA

**Document:** `docs/ERD.md`
**Version:** 1.1
**Status:** CONTROLLED BASELINE
**Parent Documents:** Master Blueprint DESATARA v3.0, Regulatory Traceability Matrix v1.0, PRD DESATARA v1.0, Business Process & Workflow Specification v1.0, RBAC & Regulatory Authority Matrix v1.0
**Database:** PostgreSQL
**Tenancy Model:** Shared Database / Shared Schema / `tenant_id`

---

# 1. PURPOSE

ERD DESATARA mendefinisikan logical data architecture yang menjadi kontrak antara requirement bisnis, workflow, authority, security, reporting, dan physical PostgreSQL schema.

Database bukan sekadar persistence layer.

Database merupakan salah satu lapisan pertahanan untuk:

- tenant isolation;
- referential integrity;
- asset lifecycle history;
- regulatory traceability;
- authority traceability;
- evidence integrity;
- workflow versioning;
- reporting reproducibility;
- auditability;
- concurrency safety;
- interoperability readiness.

---

# 2. DATABASE INVARIANTS

## DB-001 — Tenant Ownership

Semua tenant-owned record memiliki `tenant_id`.

## DB-002 — Global Identity

`users` adalah global identity.

User tidak dimiliki satu tenant.

Hubungan:

**users → tenant_memberships → tenants**

## DB-003 — Structural Tenant Isolation

Tenant isolation tidak hanya bergantung pada Laravel.

Untuk relasi antar tenant-owned entities, database harus menggunakan tenant-aware referential integrity sejauh PostgreSQL memungkinkan.

Canonical pattern:

`UNIQUE (tenant_id, id)`

dan:

`FOREIGN KEY (tenant_id, resource_id) REFERENCES resources(tenant_id, id)`

Exception harus:

- mempunyai alasan arsitektural;
- terdokumentasi;
- mempunyai application guard;
- mempunyai test tenant-isolation.

## DB-004 — Immutable History

Historical transaction tidak dihapus atau ditimpa untuk merepresentasikan current state.

## DB-005 — Public Identifier Separation

Internal PK dan public identifier dipisahkan.

## DB-006 — Monetary Precision

Nilai finansial menggunakan `NUMERIC/DECIMAL`.

Floating point dilarang untuk monetary value.

## DB-007 — Time Integrity

Authoritative timestamp disimpan secara konsisten.

Tenant timezone digunakan untuk presentation dan business interpretation.

## DB-008 — Controlled State

Critical status menggunakan controlled domain values.

## DB-009 — No Business Meaning in PK

Primary key tidak menjadi:

- kode aset;
- register number;
- document number;
- regulatory number.

## DB-010 — Historical Context

Historical record tidak boleh kehilangan makna akibat perubahan mutable master data.

## DB-011 — Concurrency Safety

Critical aggregate harus mendukung concurrency protection.

## DB-012 — Idempotent Critical Operations

Repeated critical request tidak boleh menghasilkan duplicate authoritative transaction.

---

# 3. DOMAIN MAP

Logical domain:

1. Platform & Tenant
2. Identity & Authorization
3. Regulatory
4. Reference & Classification
5. Asset
6. Acquisition
7. Location & Responsibility
8. Evidence
9. QR
10. Usage
11. Utilization
12. Safeguarding
13. Maintenance
14. Inventory
15. Valuation
16. Transfer
17. Disposal
18. Workflow
19. Approval
20. Reporting
21. Import & Integration
22. Notification
23. Audit

---

# 4. TENANTS

## `tenants`

Tenant merupakan boundary utama data desa.

Conceptual attributes:

- id
- uuid
- village_code
- name
- province
- regency
- district
- address
- timezone
- locale
- status
- activated_at
- suspended_at
- archived_at
- created_at
- updated_at

Tenant status:

- pending
- active
- suspended
- archived

`uuid` digunakan jika identifier tenant perlu diekspos di luar trusted internal context.

---

# 5. TENANT SETTINGS

## `tenant_settings`

Relationship:

**tenants 1 — 1 tenant_settings**

Menyimpan:

- branding;
- document numbering configuration;
- reporting preference;
- locale;
- operational configuration.

Tenant configuration tidak boleh menonaktifkan mandatory national rule atau security invariant.

---

# 6. USERS

## `users`

Global identity.

Conceptual attributes:

- id
- uuid
- name
- email
- password
- status
- last_login_at
- created_at
- updated_at

`users` tidak memiliki `tenant_id` sebagai ownership field.

---

# 7. TENANT MEMBERSHIPS

## `tenant_memberships`

Relationship:

**users N — N tenants**

Attributes:

- id
- tenant_id
- user_id
- status
- joined_at
- valid_from
- valid_until
- created_at
- updated_at

Membership menjadi security boundary untuk tenant-scoped user activity.

Exact uniqueness/history strategy dikunci pada Data Dictionary.

---

# 8. ROLES

## `roles`

Role definition.

Attributes:

- id
- code
- name
- scope_type
- is_system
- timestamps

Tenant-custom role hanya diperbolehkan jika implementation specification mendukungnya secara eksplisit.

---

# 9. PERMISSIONS

## `permissions`

Attributes:

- id
- code
- domain
- action
- description

Permission code harus stable.

---

# 10. ROLE PERMISSIONS

## `role_permissions`

Relationship:

**roles N — N permissions**

---

# 11. MEMBERSHIP ROLES

## `membership_roles`

Relationship:

**tenant_memberships N — N roles**

Role assignment melekat pada membership.

Role tidak melekat secara global pada user.

---

# 12. TENANT OFFICIALS

## `tenant_officials`

Merepresentasikan current/historical regulatory authority assignment.

Attributes:

- id
- tenant_id
- membership_id
- official_type
- position_name
- appointment_number
- appointment_date
- valid_from
- valid_until
- status
- evidence_document_id
- timestamps

Possible status:

- pending
- active
- expired
- revoked
- superseded

Authority bersifat period-aware.

---

# 13. AUTHORITY SNAPSHOTS

## `authority_snapshots`

Authority snapshot merupakan first-class historical record untuk critical action.

Tujuannya mempertahankan:

- actor identity;
- membership;
- tenant;
- official type;
- position;
- authority type;
- appointment reference;
- validity context;
- captured_at.

Snapshot tidak bergantung pada current `tenant_officials`.

Jika jabatan actor kemudian berubah, historical action tetap dapat diinterpretasikan.

Snapshot dapat mempunyai immutable structured payload untuk historical presentation, tetapi identity/reference penting tetap dimodelkan secara relational sejauh relevan.

---

# 14. REGULATIONS

## `regulations`

Identitas logical sumber regulasi.

---

# 15. REGULATION VERSIONS

## `regulation_versions`

Relationship:

**regulations 1 — N regulation_versions**

Menyimpan:

- version identity;
- effective_from;
- effective_until;
- status;
- source reference;
- checksum/source integrity metadata.

Published historical version tidak destructive-edit.

---

# 16. REGULATION PROVISIONS

## `regulation_provisions`

Unit ketentuan yang dapat ditelusuri seperti:

- pasal;
- ayat;
- bagian;
- lampiran requirement.

Relationship:

**regulation_versions 1 — N regulation_provisions**

---

# 17. BUSINESS RULES

## `business_rules`

Application/business interpretation dari requirement yang telah dimapping melalui Regulatory Traceability Matrix.

Mempunyai:

- code;
- description;
- effective period;
- status;
- implementation status.

---

# 18. BUSINESS RULE PROVISIONS

## `business_rule_provisions`

Explicit junction:

**regulation_provisions N — N business_rules**

Tidak mengandalkan hubungan konseptual tanpa persistence representation.

---

# 19. REGULATORY BINDINGS

## `regulatory_bindings`

Menghubungkan business rule dengan implementation artifact yang membutuhkan traceability.

Binding dapat menunjuk:

- workflow version;
- report template version;
- classification version;
- transaction type;
- requirement identifier.

Exact typed binding strategy dikunci pada Data Dictionary.

Tujuan utamanya:

**Regulation → Provision → Business Rule → Implementation Context**

---

# 20. CLASSIFICATION SCHEMES

## `classification_schemes`

Logical classification/coding system.

---

# 21. CLASSIFICATION VERSIONS

## `classification_versions`

Relationship:

**classification_schemes 1 — N classification_versions**

Published version immutable.

---

# 22. ASSET CLASSIFICATIONS

## `asset_classifications`

Hierarchical classification:

`parent_id → asset_classifications.id`

Attributes:

- classification_version_id
- parent_id
- code
- name
- level
- status

Asset classification harus mempertahankan historical classification meaning.

Exact coding scheme tetap mengikuti verified regulatory source.

---

# 23. FUNDING SOURCES

## `funding_sources`

Reference sumber dana/perolehan.

Global vs tenant scope dikunci pada Data Dictionary.

---

# 24. UNITS

## `units`

Canonical measurement unit.

---

# 25. ORGANIZATIONAL UNITS

## `organizational_units`

Tenant-owned organizational structure yang dapat menjadi konteks tanggung jawab aset.

Conceptual attributes:

- id
- tenant_id
- parent_id
- code
- name
- type
- status
- valid_from
- valid_until

---

# 26. RESPONSIBLE PARTIES

## `responsible_parties`

Canonical responsibility identity.

Tidak menggunakan arbitrary polymorphic reference.

Responsible party dapat secara eksplisit merepresentasikan:

- tenant membership; atau
- organizational unit.

Implementation menggunakan typed nullable FK/check constraint atau equivalent relational strategy yang dikunci di Data Dictionary.

Invariant:

**tepat satu responsibility target harus valid.**

Free-text name tidak menjadi authoritative responsibility identity.

---

# 27. ASSETS

## `assets`

Central asset aggregate.

Conceptual attributes:

- id
- uuid
- tenant_id
- classification_id
- asset_code
- register_number
- name
- description
- acquisition_date
- acquisition_year
- acquisition_origin
- funding_source_id
- quantity
- unit_id
- unit_price
- acquisition_value
- current_location_id
- current_responsible_party_id
- condition
- lifecycle_status
- verification_status
- lock_version
- created_by
- updated_by
- created_at
- updated_at

---

# 28. ASSET IDENTIFIERS

Identifier yang berbeda:

1. internal DB ID;
2. UUID;
3. asset code;
4. register number;
5. QR token.

Tidak boleh diperlakukan sebagai identifier yang sama.

Asset code/register uniqueness tenant-aware.

Exact uniqueness rule menunggu verified coding scheme.

---

# 29. ASSET LIFECYCLE

Lifecycle state merupakan controlled value.

Conceptual states:

- draft
- active
- inactive
- transferred
- disposed

Exact state catalog dikunci melalui Data Dictionary + Workflow.

Direct generic:

`ACTIVE → DISPOSED`

dilarang.

---

# 30. ASSET CONDITION

Condition menggunakan canonical controlled values.

Tenant tidak dapat membuat arbitrary condition yang merusak interoperabilitas/reporting.

Exact catalog mengikuti verified regulatory mapping.

---

# 31. ASSET LAND DETAILS

## `asset_land_details`

**assets 1 — 0..1 asset_land_details**

Tenant-aware relationship.

Hanya berlaku untuk compatible asset classification.

---

# 32. ASSET BUILDING DETAILS

## `asset_building_details`

**assets 1 — 0..1 asset_building_details**

Tenant-aware relationship.

---

# 33. ASSET VEHICLE DETAILS

## `asset_vehicle_details`

**assets 1 — 0..1 asset_vehicle_details**

Tenant-aware relationship.

---

# 34. ASSET EQUIPMENT DETAILS

## `asset_equipment_details`

**assets 1 — 0..1 asset_equipment_details**

Common fields tidak diduplikasi.

---

# 35. ASSET ACQUISITIONS

## `asset_acquisitions`

**assets 1 — N asset_acquisitions**

Menyimpan provenance:

- acquisition type;
- source;
- date;
- value;
- funding;
- counterparty/reference;
- evidence context.

DESATARA bukan procurement accounting engine.

---

# 36. ASSET LOCATIONS

## `asset_locations`

Tenant-owned hierarchical location.

`parent_id → asset_locations.id`

Possible hierarchy:

**Desa → Kompleks → Gedung → Ruangan → Area**

Parent dan child wajib berada dalam tenant yang sama.

---

# 36A. ASSET CLASSIFICATION ASSIGNMENTS

## `asset_classification_assignments`

Authoritative classification history for an asset. `assets.classification_id` is only the current projection.

Attributes:

- id
- tenant_id
- asset_id
- classification_id
- valid_from
- valid_until nullable
- assignment_type
- reason nullable
- assigned_by
- workflow_instance_id nullable
- created_at

The asset reference is tenant-aware. Classification references the versioned canonical `asset_classifications` row. A tenant/asset may have only one open assignment (`valid_until IS NULL`) at a time. Initial registration creates the first assignment; reclassification closes the previous interval and appends a new assignment in the same transaction as the current projection update.

Historical assignments are not rewritten when classification masters or the current projection change.

---

# 36B. ASSET CONDITION EVENTS

## `asset_condition_events`

Append-oriented authoritative history for condition changes.

Attributes:

- id
- uuid
- tenant_id
- asset_id
- previous_condition nullable for initial event
- new_condition
- effective_at
- reason nullable
- source_type
- actor_id
- workflow_instance_id nullable
- idempotency_key nullable
- created_at

`assets.condition` is the current projection. Event insertion and projection update are atomic. Canonical condition validation follows the applicable regulatory/reference rule; the history table does not create a tenant-defined condition taxonomy.

---

# 36C. ASSET LIFECYCLE EVENTS

## `asset_lifecycle_events`

Durable history of lifecycle state transitions.

Attributes:

- id
- uuid
- tenant_id
- asset_id
- from_status nullable for initial event
- to_status
- transition_type
- effective_at
- reason nullable
- actor_id
- workflow_instance_id nullable
- idempotency_key nullable
- created_at

`assets.lifecycle_status` is the current projection. Lifecycle events do not authorize a transition by themselves: permission, authority, prerequisite, evidence, approval, and workflow guards remain governed by RBAC/Workflow contracts. Direct arbitrary edits of `assets.lifecycle_status` are prohibited.

---

# 36D. CONTROLLED ASSET CORRECTIONS

## `asset_corrections`

Append-only evidence of an applied administrative correction. It is not a generic patch mechanism.

Attributes:

- id
- uuid
- tenant_id
- asset_id
- correction_type
- corrected_fields
- before_values
- after_values
- reason
- reference nullable
- applied_by
- applied_at
- workflow_instance_id nullable
- idempotency_key nullable
- created_at

`corrected_fields`, `before_values`, and `after_values` are structured JSON objects limited by server-side domain allowlists. A material correction follows WF-AST-003 and records before/after. If the corrected fact is itself historical (classification, location, responsible party, condition, lifecycle), correction must append the corresponding domain history/event and update its current projection atomically; it must never rewrite the old historical row.

---

# 37. CURRENT LOCATION PROJECTION

`assets.current_location_id`

merupakan current-state projection.

Historical truth:

**asset_mutations**

Perubahan keduanya dilakukan transactionally.

Current projection tidak menggantikan history.

---

# 38. ASSET MUTATIONS

## `asset_mutations`

Attributes:

- id
- tenant_id
- asset_id
- origin_location_id
- destination_location_id
- mutation_type
- reason
- effective_at
- status
- requested_by
- executed_by
- workflow_instance_id bila relevan
- idempotency_key bila relevan
- timestamps

Semua asset/location/member references tenant-aware.

Mutation history append-oriented setelah authoritative execution.

---

# 39. RESPONSIBILITY ASSIGNMENTS

## `asset_responsibility_assignments`

Menyimpan:

- tenant;
- asset;
- responsible party;
- valid_from;
- valid_until;
- assignment reference;
- assigned_by;
- workflow context.

Current responsible party dapat diproyeksikan pada `assets`.

Historical assignments tetap authoritative untuk history.

---

# 40. DOCUMENTS

## `documents`

Private file metadata.

Attributes:

- id
- uuid
- tenant_id
- document_type
- document_number
- document_date
- storage_disk
- storage_path
- original_filename
- mime_type
- size
- checksum
- uploaded_by
- timestamps

File private-by-default.

---

# 41. SUBJECT TYPES

## `subject_types`

Controlled registry untuk generic internal relation.

Menyimpan domain subject yang secara eksplisit diizinkan.

Arbitrary class name tidak digunakan sebagai authoritative type.

---

# 42. DOCUMENT LINKS

## `document_links`

Generic evidence relationship:

- id
- tenant_id
- document_id
- subject_type_id
- subject_id
- purpose
- timestamps

Generic evidence relation diperbolehkan untuk fleksibilitas evidence.

Namun application layer wajib memverifikasi:

**document tenant = link tenant = subject tenant**

High-risk domain dapat menggunakan explicit typed evidence association apabila stronger database integrity dibutuhkan.

---

# 43. ASSET PHOTOS

## `asset_photos`

Photo evidence metadata:

- tenant_id
- asset_id
- document_id
- category
- captured_at
- uploaded_by
- notes.

Asset/document references tenant-aware.

---

# 44. ASSET QR TOKENS

## `asset_qr_tokens`

**assets 1 — N asset_qr_tokens**

Attributes:

- tenant_id
- asset_id
- token_hash
- status
- issued_at
- revoked_at
- rotated_from_id.

Opaque token dapat disimpan dalam hashed representation.

Old token tidak boleh berfungsi setelah revoke.

---

# 45. USAGE DETERMINATIONS

## `asset_usage_determinations`

Header:

- tenant_id
- period/year
- decision reference
- status
- authority_snapshot_id
- workflow_instance_id
- finalized_at.

---

# 46. USAGE ITEMS

## `asset_usage_items`

**asset_usage_determinations 1 — N asset_usage_items**

Mereferensikan asset dan historical context yang diperlukan.

Cross-tenant item prohibited.

---

# 47. ASSET UTILIZATIONS

## `asset_utilizations`

Conceptual:

- tenant_id
- asset_id
- utilization_type
- counterparty
- start_at
- end_at
- value/revenue reference
- status
- workflow_instance_id.

Full implementation post-MVP.

---

# 48. ASSET SAFEGUARDS

## `asset_safeguards`

Menyimpan:

- tenant;
- asset;
- safeguard category;
- finding;
- action;
- evidence context;
- verification;
- status.

Finding bukan automatic legal verdict.

---

# 49. ASSET MAINTENANCES

## `asset_maintenances`

Menyimpan:

- tenant_id
- asset_id
- maintenance_type
- planned_at
- completed_at
- vendor
- planned_cost
- actual_cost
- funding_source_id
- condition_before
- condition_after
- status
- actor
- timestamps

Maintenance merupakan historical transaction.

---

# 50. INVENTORY SESSIONS

## `inventory_sessions`

Attributes:

- id
- uuid
- tenant_id
- period
- scope
- status
- started_at
- review_at
- finalized_at
- created_by
- workflow_version/context jika diperlukan
- lock_version
- timestamps

---

# 51. INVENTORY SESSION ASSIGNMENTS

## `inventory_session_assignments`

Menyimpan authorized inventory team/member assignment.

Session dan membership harus berada dalam tenant yang sama.

---

# 52. INVENTORY ITEMS

## `inventory_items`

Merupakan inventory-time snapshot, bukan hanya pointer ke current `assets`.

Minimal logical snapshot:

- tenant_id
- inventory_session_id
- asset_id
- asset_uuid_snapshot
- asset_code_snapshot
- register_number_snapshot
- asset_name_snapshot
- classification_id_snapshot
- classification_code_snapshot
- classification_name_snapshot
- expected_location_id
- expected_location_snapshot
- observed_location_id
- observed_location_snapshot
- expected_condition
- observed_condition
- lifecycle_status_snapshot
- verification_result
- verified_by
- verified_at
- evidence context.

Historical inventory tetap dapat dipahami walaupun asset master/classification/location kemudian berubah.

---

# 53. INVENTORY DISCOVERIES

## `inventory_discoveries`

Physical object ditemukan tetapi belum mempunyai authoritative match.

Resolution:

- link_existing
- register_new
- unidentified
- duplicate_suspected

Scanner tidak otomatis menciptakan authoritative asset.

---

# 54. INVENTORY DISCREPANCIES

## `inventory_discrepancies`

Menyimpan:

- tenant;
- inventory session;
- inventory item/asset;
- discrepancy type;
- description;
- status;
- proposed resolution;
- reviewer;
- timestamps.

---

# 55. INVENTORY RECONCILIATIONS

## `inventory_reconciliations`

Menyimpan explicit reconciliation decision.

Possible result:

- no_change;
- asset_correction;
- mutation;
- condition_update;
- new_asset;
- duplicate_investigation;
- other controlled action.

Reconciliation mereferensikan resulting domain action/transaction.

Tidak ada generic arbitrary master overwrite.

---

# 56. ASSET VALUATIONS

## `asset_valuations`

Historical valuation:

- tenant;
- asset;
- purpose;
- valuation date;
- amount;
- valuer;
- authority/reference;
- authority snapshot bila relevan;
- workflow instance;
- evidence;
- status.

Valuation tidak overwrite historical valuation sebelumnya.

---

# 57. ASSET TRANSFERS

## `asset_transfers`

Header transaction:

- tenant;
- transfer type;
- request date;
- status;
- workflow_instance_id;
- authority snapshot;
- formal decision;
- execution metadata;
- lock_version;
- idempotency context.

---

# 58. ASSET TRANSFER ITEMS

## `asset_transfer_items`

**asset_transfers 1 — N asset_transfer_items**

Transfer/item/asset harus tenant-consistent.

---

# 59. ASSET DISPOSALS

## `asset_disposals`

Header:

- tenant;
- reason/type;
- request;
- status;
- workflow_instance_id;
- authority snapshot;
- formal decision;
- execution metadata;
- lock_version;
- idempotency context.

---

# 60. ASSET DISPOSAL ITEMS

## `asset_disposal_items`

**asset_disposals 1 — N asset_disposal_items**

Disposal tidak menghapus `assets`.

Historical asset tetap tersedia.

---

# 61. WORKFLOW DEFINITIONS

## `workflow_definitions`

Logical workflow family.

Examples:

- asset_mutation;
- usage_determination;
- utilization;
- valuation;
- transfer;
- disposal;
- report_finalization.

---

# 62. WORKFLOW VERSIONS

## `workflow_versions`

**workflow_definitions 1 — N workflow_versions**

Attributes:

- version;
- effective_from;
- effective_until;
- status;
- definition metadata.

Lifecycle:

`DRAFT → PUBLISHED → SUPERSEDED`

Published version immutable.

---

# 63. WORKFLOW INSTANCES

## `workflow_instances`

First-class execution instance.

Attributes conceptual:

- id
- uuid
- tenant_id
- workflow_version_id
- subject_type_id
- subject_id
- current_state
- started_by
- started_at
- completed_at
- cancelled_at
- lock_version
- timestamps.

Tidak semua workflow membutuhkan approval.

`workflow_instances` menyimpan lifecycle execution secara umum.

---

# 64. WORKFLOW TRANSITIONS

## `workflow_transitions`

Append-only transition history.

Menyimpan:

- tenant_id
- workflow_instance_id
- from_state
- to_state
- action
- actor_membership_id
- authority_snapshot_id jika relevan
- reason/notes
- idempotency_key jika relevan
- occurred_at.

Historical transition tidak destructive-edit.

---

# 65. APPROVAL REQUESTS

## `approval_requests`

Approval merupakan extension dari workflow yang membutuhkan approval.

Conceptual:

- id
- uuid
- tenant_id
- workflow_instance_id
- subject_type_id
- subject_id
- status
- requested_by
- submitted_at
- completed_at
- lock_version.

Approval tidak menjadi universal replacement untuk workflow.

---

# 66. APPROVAL STEPS

## `approval_steps`

Instantiated approval step.

Fields:

- tenant_id
- approval_request_id
- sequence
- required_permission_id
- authority_requirement
- status
- timestamps.

---

# 67. APPROVAL ACTIONS

## `approval_actions`

Append-only.

Fields:

- tenant_id
- approval_request_id
- approval_step_id
- actor_membership_id
- authority_snapshot_id
- action
- notes
- idempotency_key
- acted_at.

Approval action tidak bergantung pada current role/official data untuk historical interpretation.

---

# 68. EXTERNAL APPROVALS

## `external_approvals`

Mencatat external authority decision.

Fields:

- tenant;
- workflow/subject context;
- authority organization;
- decision type;
- document reference;
- decision date;
- status.

External approval bukan internal application approval.

---

# 69. REPORTING PERIODS

## `reporting_periods`

Tenant-owned reporting period.

Fields:

- tenant_id
- year
- semester/type
- start_date
- end_date
- deadline
- status.

---

# 70. REPORT TEMPLATES

## `report_templates`

Logical report family.

---

# 71. REPORT TEMPLATE VERSIONS

## `report_template_versions`

Published template version immutable.

Stores:

- version;
- effective period;
- regulatory context;
- schema/template definition;
- status.

---

# 72. ASSET REPORTS

## `asset_reports`

Report instance:

- tenant_id
- reporting_period_id
- report_template_version_id
- status
- revision_number
- parent_report_id
- generated_by
- reviewed_by
- finalized_by
- workflow_instance_id
- lock_version
- timestamps.

---

# 73. REPORT SNAPSHOTS

## `report_snapshots`

**Required untuk finalized report.**

Finalized report tidak dianggap lengkap tanpa immutable snapshot.

Snapshot mempertahankan minimal:

- tenant identity;
- report dataset;
- official/signatory context;
- authority context;
- classification context;
- template version;
- regulatory context;
- reporting period;
- generation metadata;
- finalization timestamp;
- artifact reference;
- artifact checksum/hash.

Current master data tidak dapat mengubah finalized report.

---

# 74. REPORT REVISION

Revision membuat report instance baru.

Relationship:

`asset_reports.parent_report_id → asset_reports.id`

Previous finalized snapshot tetap immutable.

Tidak ada in-place edit terhadap finalized report.

---

# 75. IMPORT JOBS

## `import_jobs`

Menyimpan:

- tenant;
- importer;
- source document;
- mapping;
- validation state;
- status;
- totals;
- timestamps.

---

# 76. IMPORT STAGING

Import membutuhkan staging/error representation sebelum authoritative write.

Implementation dapat menggunakan:

- `import_rows`;
- `import_errors`;
- atau equivalent normalized staging structure.

Import tidak mempercayai `tenant_id` dari file.

---

# 77. INTEGRATION EXPORTS

## `integration_exports`

Attributes:

- tenant;
- target system;
- format/version;
- status;
- requested_by;
- generated_at;
- exported_at;
- checksum;
- reconciliation context.

Canonical state:

- NOT_PREPARED
- READY_FOR_EXPORT
- EXPORTED
- RECONCILED

`SYNCED` tidak digunakan tanpa actual supported synchronization.

---

# 78. NOTIFICATIONS

## `notifications`

Tenant-aware notification metadata.

Recipient harus mempunyai visibility terhadap referenced resource.

Notification tidak menjadi authorization bypass.

---

# 79. AUDIT LOGS

## `audit_logs`

Append-oriented.

Fields:

- id
- tenant_id nullable untuk platform event
- actor_user_id
- actor_membership_id
- event
- subject_type_id
- subject_id
- before_state
- after_state
- authority_snapshot/context
- workflow_version/instance context
- request_id
- ip_address
- user_agent
- occurred_at.

Normal application operation tidak mengubah historical audit event.

---

# 80. TENANT-AWARE FOREIGN KEY POLICY

Untuk tenant-owned parent:

`UNIQUE (tenant_id, id)`

menjadi candidate tenant-aware key.

Tenant-owned child menggunakan composite FK sejauh practical:

`FOREIGN KEY (tenant_id, asset_id) REFERENCES assets(tenant_id, id)`

Policy berlaku secara default untuk:

- assets → locations;
- subtype → assets;
- acquisitions → assets;
- mutations → assets/locations;
- responsibility → assets;
- document links → documents;
- photos → assets/documents;
- QR → assets;
- usage items → usage header/assets;
- maintenance → assets;
- inventory children → inventory sessions/assets;
- transfer items → transfer/assets;
- disposal items → disposal/assets;
- workflow children → workflow instances;
- approval children → approval request;
- reports → reporting periods;
- tenant-owned user references melalui tenant membership.

Exception harus documented.

---

# 81. GENERIC RELATION POLICY

Generic relation hanya digunakan jika domain flexibility benar-benar membutuhkan.

Setiap generic subject menggunakan:

- controlled `subject_types`;
- allowlist;
- tenant validation;
- policy validation;
- test coverage.

Critical regulatory relationship menggunakan typed FK bila generic relation mengurangi integrity secara material.

---

# 82. CLIENT TENANT RULE

Client tidak menjadi source of truth untuk `tenant_id`.

Tenant berasal dari active server tenant context.

Sistem tidak mempercayai tenant dari:

- hidden input;
- query parameter;
- Inertia payload;
- JSON;
- CSV;
- imported file.

---

# 83. DELETE STRATEGY

### Established Assets

No normal hard-delete.

### Historical Transactions

No destructive deletion.

### Audit

Append-only.

### Workflow History

Preserved.

### Finalized Reports

Immutable.

### Published Workflow/Template/Regulatory Version

Immutable/superseded.

### Invalid Draft/Setup Data

Controlled deletion dapat diperbolehkan jika belum membentuk administrative history.

---

# 84. SOFT DELETE POLICY

Soft delete bukan lifecycle state.

`deleted_at` tidak merepresentasikan:

- disposed;
- transferred;
- rejected;
- cancelled;
- expired;
- superseded.

Gunakan explicit state.

---

# 85. CONCURRENCY MODEL

Critical aggregates mempunyai concurrency strategy.

Minimal candidates:

- workflow_instances;
- approval_requests;
- inventory_sessions;
- asset_reports;
- asset_transfers;
- asset_disposals;
- sequence/document numbering allocation;
- critical asset mutation.

Logical ERD menyediakan `lock_version` pada aggregate yang membutuhkan optimistic concurrency.

Implementation boleh menggunakan:

- optimistic locking;
- row-level locking;
- transaction;
- unique constraint;
- advisory locking jika justified.

---

# 86. IDEMPOTENCY MODEL

Critical command dapat memiliki idempotency identifier.

Required consideration:

- approval action;
- report finalization;
- transfer execution;
- disposal execution;
- inventory reconciliation;
- mutation execution;
- integration operation.

Idempotency key harus scoped secara aman, misalnya tenant + operation context.

Repeated request menghasilkan authoritative result yang sama atau controlled conflict, bukan duplicate transaction.

---

# 87. DOCUMENT NUMBERING

Business/document number terpisah dari PK/UUID.

Concurrency-safe sequence allocation wajib.

Exact model—misalnya `numbering_sequences`—dikunci di Data Dictionary setelah numbering requirements selesai.

---

# 88. HISTORICAL SNAPSHOT POLICY

Snapshot/reference version digunakan apabila current mutable state dapat mengubah interpretasi historical record.

Wajib dipertimbangkan untuk:

- authority;
- workflow;
- classification;
- regulation;
- report template;
- finalized report;
- inventory expectation;
- formal decision context.

Snapshot tidak digunakan sembarangan sebagai pengganti relational design.

---

# 89. INDEXING BASELINE

Candidate indexes:

- `tenant_id`
- `(tenant_id, asset_code)`
- `(tenant_id, register_number)`
- `(tenant_id, lifecycle_status)`
- `(tenant_id, classification_id)`
- `(tenant_id, current_location_id)`
- `(tenant_id, current_responsible_party_id)`
- acquisition year
- inventory session/status
- approval status
- workflow current state
- reporting period/status
- audit `occurred_at`
- document checksum
- active authority period
- active QR token lookup

Exact indexes mengikuti query profile dan Data Dictionary.

---

# 90. POSTGRESQL TYPE BASELINE

Recommended baseline:

- internal PK: `BIGINT`;
- public identifier: UUID;
- monetary value: `NUMERIC`;
- authoritative timestamp: timezone-aware strategy;
- snapshot payload: `JSONB` hanya jika justified;
- status: controlled domain value;
- boolean: hanya true binary semantics.

JSONB tidak digunakan untuk menghindari relational modeling.

---

# 91. CHECK CONSTRAINT BASELINE

Database constraint digunakan untuk invariant sederhana seperti:

- quantity > 0;
- amount >= 0 jika domain mengharuskan;
- valid_from <= valid_until;
- start <= end;
- exactly-one responsible-party target;
- known state jika implementation strategy memungkinkan.

Complex regulatory decision tetap berada di domain/application layer.

---

# 92. DATA OWNERSHIP

## Global

Contoh:

- permissions;
- regulations;
- regulation versions;
- business rules;
- classification definitions jika nasional/global;
- workflow definitions;
- report template definitions.

## Tenant-Owned

Contoh:

- memberships;
- officials;
- organizational units;
- responsible parties;
- assets;
- documents;
- locations;
- transactions;
- inventory;
- workflow instances;
- approvals;
- reports;
- imports;
- notifications.

## Platform-Level

Contoh:

- tenant provisioning;
- platform configuration;
- platform audit.

---

# 93. CORE RELATIONSHIP MAP

```text
users
  |
  +--- tenant_memberships --- tenants
             |
             +--- membership_roles --- roles
             |                          |
             |                    role_permissions
             |                          |
             |                     permissions
             |
             +--- tenant_officials
             |
             +--- authority_snapshots

tenants
  |
  +--- organizational_units
  +--- responsible_parties
  |
  +--- assets
  |      |
  |      +--- asset_acquisitions
  |      +--- asset_land_details
  |      +--- asset_building_details
  |      +--- asset_vehicle_details
  |      +--- asset_equipment_details
  |      +--- asset_qr_tokens
  |      +--- asset_photos
  |      +--- asset_classification_assignments
  |      +--- asset_mutations
  |      +--- asset_responsibility_assignments
  |      +--- asset_condition_events
  |      +--- asset_lifecycle_events
  |      +--- asset_corrections
  |      +--- asset_maintenances
  |      +--- asset_valuations
  |
  +--- asset_locations
  |
  +--- documents
  |      |
  |      +--- document_links
  |
  +--- inventory_sessions
  |      |
  |      +--- inventory_session_assignments
  |      +--- inventory_items
  |      +--- inventory_discoveries
  |      +--- inventory_discrepancies
  |              |
  |              +--- inventory_reconciliations
  |
  +--- workflow_instances
  |      |
  |      +--- workflow_transitions
  |      |
  |      +--- approval_requests
  |              |
  |              +--- approval_steps
  |              +--- approval_actions
  |
  +--- asset_usage_determinations
  |      |
  |      +--- asset_usage_items
  |
  +--- asset_utilizations
  +--- asset_safeguards
  |
  +--- asset_transfers
  |      +--- asset_transfer_items
  |
  +--- asset_disposals
  |      +--- asset_disposal_items
  |
  +--- reporting_periods
         |
         +--- asset_reports
                |
                +--- report_snapshots
```

---

# 94. REGULATORY RELATIONSHIP MAP

```text
regulations
    |
regulation_versions
    |
regulation_provisions
    |
business_rule_provisions
    |
business_rules
    |
regulatory_bindings
    |
workflow / template / classification / domain context
```

---

# 95. WORKFLOW RELATIONSHIP MAP

```text
workflow_definitions
       |
workflow_versions
       |
workflow_instances
       |
workflow_transitions

workflow_instances
       |
approval_requests
       |
approval_steps
       |
approval_actions
```

Approval merupakan optional workflow capability.

---

# 96. INVENTORY RELATIONSHIP MAP

```text
inventory_sessions
       |
       +--- inventory_session_assignments
       |
       +--- inventory_items
       |
       +--- inventory_discoveries
       |
       +--- inventory_discrepancies
                   |
            inventory_reconciliations
                   |
              domain action
```

Inventory reconciliation tidak melakukan arbitrary master overwrite.

---

# 97. REPORTING RELATIONSHIP MAP

```text
report_templates
       |
report_template_versions
       |
asset_reports
       |
report_snapshots
```

Finalized report wajib memiliki immutable snapshot.

---

# 98. SECURITY INVARIANTS

Schema harus mencegah sejauh mungkin:

1. asset tenant A menggunakan location tenant B;
2. subtype tenant A menunjuk asset tenant B;
3. document tenant A ditautkan ke subject tenant B;
4. inventory tenant A memverifikasi asset tenant B;
5. approval tenant A memproses subject tenant B;
6. workflow tenant A menjalankan subject tenant B;
7. report tenant A memakai period/data tenant B;
8. transfer/disposal tenant A menggunakan asset tenant B;
9. membership tenant A digunakan sebagai actor tenant B;
10. queue/background operation mengubah resource tenant lain.

Cross-tenant reference merupakan integrity/security failure.

---

# 99. HISTORY INVARIANTS

Database harus tetap dapat menjawab:

- di mana aset sebelumnya?
- siapa penanggung jawab sebelumnya?
- bagaimana aset diperoleh?
- bagaimana kondisinya ketika stock opname?
- discrepancy apa yang ditemukan?
- bagaimana discrepancy direkonsiliasi?
- siapa melakukan transition?
- siapa menyetujui?
- dalam jabatan/kewenangan apa?
- workflow versi berapa?
- aturan versi berapa?
- classification versi berapa?
- laporan menggunakan template versi apa?
- data apa yang difinalisasi?
- siapa pejabat/signatory saat laporan difinalisasi?

---

# 100. PHYSICAL SCHEMA GATE

Migration Laravel/PostgreSQL hanya dibuat setelah:

1. entity ownership dikunci;
2. composite FK coverage dikunci;
3. exact PostgreSQL types dikunci;
4. controlled state strategy dikunci;
5. classification model dikunci;
6. responsible-party constraints dikunci;
7. generic relation implementation dikunci;
8. authority snapshot columns dikunci;
9. workflow state representation dikunci;
10. report snapshot structure dikunci;
11. inventory snapshot structure dikunci;
12. concurrency/idempotency implementation dikunci;
13. index/constraint baseline dikunci.

Detail tersebut menjadi tugas **Data Dictionary v1.1**.

---

# 101. DATA DICTIONARY OPEN ITEMS

ERD tidak menebak detail berikut:

- exact PostgreSQL column types;
- UUID generation implementation;
- enum vs lookup/check strategy;
- exact classification fields;
- exact coding/register uniqueness;
- exact authority snapshot columns;
- exact subject-type implementation;
- exact workflow definition format;
- exact inventory snapshot types;
- exact report snapshot JSON/relational boundary;
- exact regulatory-binding implementation;
- exact `lock_version` strategy;
- idempotency storage/index;
- numbering sequence implementation;
- retention/archive details;
- exact indexes;
- exact FK delete/update actions.

Semua dikunci pada Data Dictionary.

---

# 102. ERD ACCEPTANCE GATE

ERD v1.1 dinyatakan memenuhi baseline karena logical architecture telah menetapkan:

- tenant ownership;
- tenant-aware relationship policy;
- global user vs tenant membership;
- authority assignment;
- historical authority snapshot;
- asset lifecycle history;
- explicit responsible-party model;
- generic evidence boundary;
- inventory snapshot;
- reconciliation boundary;
- workflow definition/version;
- workflow instance/transition;
- approval as workflow extension;
- regulatory traceability junction;
- report immutable snapshot;
- concurrency boundary;
- idempotency requirement;
- auditability;
- no administrative hard-delete.

Physical detail tetap menunggu Data Dictionary.

---

# 103. SOURCE OF TRUTH

Jika ERD bertentangan dengan:

1. applicable regulation;
2. Regulatory Traceability Matrix;
3. Master Blueprint;
4. PRD;
5. Workflow Specification;
6. RBAC & Regulatory Authority Matrix;

maka:

**STOP → IDENTIFY → TRACE → RESOLVE → UPDATE DOCUMENT → IMPLEMENT**

Database tidak boleh menjadi sumber business rule baru secara diam-diam.

---

# 104. FINAL ERD PRINCIPLES

> **Current state boleh dioptimalkan untuk pembacaan, tetapi historical truth tidak boleh dikorbankan demi kemudahan query.**

> **Tenant isolation bukan convention Laravel semata; relational database harus ikut menegakkannya sejauh praktis.**

> **Workflow bukan approval, tetapi approval dapat menjadi bagian dari workflow.**

> **Historical authority harus tetap dapat dibuktikan setelah user, role, jabatan, dan struktur organisasi berubah.**

> **Finalized report adalah historical artifact, bukan live view dari mutable database.**

---

# 105. STATUS

**ERD DESATARA v1.0 — LOCKED**

ERD telah melewati logical architecture hardening untuk:

- tenant isolation;
- referential integrity;
- authority history;
- workflow persistence;
- approval persistence;
- regulatory traceability;
- inventory reconciliation;
- report immutability;
- concurrency;
- idempotency;
- historical integrity.

Tahap berikutnya:

**Data Dictionary DESATARA v1.0**

