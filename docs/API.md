# API ARCHITECTURE & CONTRACT BASELINE — DESATARA

**Document Version:** 1.0  
**Status:** CONTROLLED BASELINE
**Product:** DESATARA — Platform Pengelolaan Aset Desa  
**Boundary:** Integration / external / future mobile / M2M

## 1. Position

First-party web DESATARA menggunakan **Laravel + Inertia.js + session authentication**. REST API bukan transport default antara Vue dan Laravel. API dibuat hanya ketika ada boundary yang memang memerlukannya.

Versioned API menggunakan prefix: `/api/v1/...` (tanpa spasi pada implementasi). Endpoint tidak boleh dibuat hanya untuk menduplikasi first-party Inertia actions.

## 2. Contract principles

- Laravel/domain layer tetap authoritative.  
- API tidak boleh memiliki business rule alternatif.  
- Tenant isolation, RBAC, regulatory authority, SoD, workflow state, concurrency, audit, evidence, dan historical integrity sama ketatnya dengan web.  
- Public identifiers menggunakan UUID/opaque identifier; sequential internal IDs tidak diekspos sebagai public contract.  
- `tenant_id` dari payload tidak pernah dipercaya untuk menentukan ownership.

## 3. Authentication and authorization

External API authentication dipisahkan dari first-party session design. Exact credential mechanism dipilih ketika consumer pertama didefinisikan. Credential harus scoped, revocable, auditable, dan tidak mengubah permission/authority model.

Setiap request tenant-owned harus resolve tenant context server-side dan menjalankan authorization terhadap actor/credential, membership/scope, resource tenant, workflow state, dan authority bila tindakan regulatif.

## 4. HTTP contract

Gunakan semantic HTTP status secara konsisten: `200/201/204` sukses, `400` malformed request, `401` unauthenticated, `403` unauthorized, `404` not found/non-disclosive resource boundary, `409` state/concurrency conflict, `422` validation failure, `429` rate limited.

Validation error harus machine-readable dan field-addressable. Error response tidak boleh membocorkan stack trace, secret, cross-tenant existence, storage path, atau internal implementation detail.

## 5. Collections

List endpoint yang benar-benar diperlukan menggunakan server-side pagination. Filtering dan sorting memakai allowlist field/operator; arbitrary SQL-like expressions dilarang. Response harus stabil dan version-aware.

## 6. Mutation safety

Critical mutation menggunakan transaction dan optimistic concurrency/version baseline yang sama dengan web. Endpoint yang berpotensi retry atau external side effect harus mendukung idempotency sesuai domain. Duplicate submission tidak boleh menghasilkan duplicate regulatory/domain action.

## 7. Files and evidence

API tidak mengekspos private storage path. Upload mengikuti validation, authorization, malware-scan/storage-status contract, dan evidence immutability. Download menggunakan authorized delivery mechanism. Finalized/linked evidence tidak dapat diam-diam ditimpa.

## 8. Observability and audit

Request penting membawa/generate correlation ID. Authentication failure, authorization denial yang relevan, privileged action, approval, export, integration action, QR/public access, dan security event dicatat sesuai Security/Audit contract tanpa menyimpan secret sensitif.

## 9. Rate limits and abuse

Rate limit ditetapkan per consumer/boundary dan diperketat untuk authentication, public lookup, export, upload, dan expensive endpoints. Nilai numerik dikunci berdasarkan deployment/load evidence; tidak diasumsikan di baseline ini.

## 10. Versioning and deprecation

Breaking API change membutuhkan versi baru atau migration path eksplisit. `/api/v1` tidak boleh berubah secara breaking diam-diam. Deprecation harus terdokumentasi sebelum removal dan mempertimbangkan consumer aktif.

## 11. OpenAPI

Machine-readable OpenAPI menjadi kontrak endpoint aktual ketika endpoint pertama diimplementasikan, di `openapi/desatara-v1.yaml`. OpenAPI tidak boleh mendeklarasikan endpoint yang belum tersedia sebagai production capability. CI nantinya memvalidasi syntax/contract drift.

## 12. Interoperability honesty

State interop tetap `NOT_PREPARED`, `READY_FOR_EXPORT`, `EXPORTED`, atau `RECONCILED` sesuai kontrak domain. Jangan menggunakan `SYNCED` tanpa integrasi resmi yang benar-benar membuktikan sinkronisasi.

## 13. Endpoint rollout

Endpoint ditambahkan per batch domain setelah use case/consumer jelas. API v1 tidak dimulai sebagai generic CRUD surface. Endpoint regulatif harus mereferensikan workflow, permission, authority, evidence, concurrency, audit, dan acceptance test terkait.

**API ARCHITECTURE & CONTRACT BASELINE DESATARA v1.0 — LOCKED**
