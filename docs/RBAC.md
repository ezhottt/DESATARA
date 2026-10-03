# RBAC & REGULATORY AUTHORITY MATRIX — DESATARA

**Document Version:** 1.0  
**Status:** CONTROLLED BASELINE
**Product:** DESATARA — Platform Pengelolaan Aset Desa  
**Upstream:** Architecture v3.0, RTM v1.0, PRD v1.0, Workflows v1.0

## 1. Core model

DESATARA memisahkan empat konsep: **user**, **tenant membership**, **application role/permission**, dan **regulatory official/authority**. Permission teknis tidak sama dengan kewenangan regulatif. Tombol atau permission `approve` tidak boleh menciptakan kewenangan hukum.

User bersifat global. Akses operasional selalu dievaluasi dalam active tenant melalui membership yang valid. Semua keputusan tenant-owned wajib server-side authorized.

## 2. Default roles

| Role | Scope | Baseline purpose |
|---|---|---|
| Platform Admin | platform | administrasi platform; bukan operator aset tenant |
| Tenant Admin | tenant | administrasi aplikasi tenant |
| Kepala Desa | tenant | fungsi aplikasi sesuai permission dan authority assignment yang sah |
| Sekretaris Desa | tenant | fungsi aplikasi sesuai permission dan authority assignment yang sah |
| Pengurus Aset | tenant | operasi pengelolaan aset sesuai permission |
| BPD / Monitoring | tenant | monitoring sesuai ruang lingkup yang diberikan |
| Auditor / Pemeriksa | tenant/scope | read-only pada ruang lingkup pemeriksaan |
| Viewer | tenant/scope | read-only terbatas |

Nama role dapat disesuaikan. Permission tetap canonical dan role tidak boleh dipakai sebagai shortcut untuk regulatory authority.

## 3. Permission baseline

Permission families minimal mencakup `asset.view/create/update`, document management, mutation, maintenance, inventory execution, transfer/disposal request, approval view/action, report view/export, audit view, user management, dan tenant settings. Exact permission catalog dikembangkan pada B03 tanpa melemahkan matriks ini.

Semua permission diperiksa melalui server-side Policy/Gate atau domain authorization service. UI visibility hanya presentation concern.

## 4. Authority model

Regulatory authority berasal dari official/authority assignment yang memiliki tenant, subject membership/official bila relevan, `authority_code`, scope, validity period, status, dan evidence melalui document links. Authority harus valid pada waktu tindakan dan diperiksa ulang pada execution/finalization bila workflow memisahkan approval dari eksekusi.

Decisive action menyimpan immutable `authority_snapshot`, termasuk actor user, membership, official bila ada, authority type/code, masa berlaku, dan appointment reference yang tersedia.

## 5. Role × capability baseline

| Capability | Platform Admin | Tenant Admin | Kades/Sekdes | Pengurus Aset | Monitoring/Auditor/Viewer |
|---|---|---|---|---|---|
| Platform administration | permitted by platform permission | — | — | — | — |
| Tenant administration | only via explicit support grant where applicable | permitted by tenant permission | per permission | — | read-only if granted |
| View tenant assets | no automatic access | per permission | per permission | per permission | scoped read-only |
| Operational asset mutation | no automatic access | per permission, subject to workflow | per permission + authority where required | per permission | no |
| Regulatory/formal approval | no automatic access | permission alone insufficient | permission + valid authority | only if valid authority explicitly permits | no |
| Audit view | platform/security scope only | per permission | per permission | limited if granted | scoped read-only |

Matrix ini adalah baseline access contract, bukan klaim bahwa setiap role memiliki semua capability pada kolomnya. Permission, resource scope, workflow state, tenant context, dan authority tetap harus lolos bersama.

## 6. Separation of duties

Requester/submitter dan approver identities dipersist. Self-approval diblokir ketika workflow mensyaratkan separation of duties. Approval action append-only; perubahan keputusan menghasilkan action baru sesuai workflow, bukan overwrite history.

## 7. Platform support access

Platform Admin tidak otomatis dapat membaca atau memutasi data operasional tenant. Cross-tenant support memerlukan `platform_support_grants` yang eksplisit: target tenant, scope, reason, granted_by, validity, revocation, correlation/audit evidence. Semua penggunaan grant diaudit.

## 8. Tenant state

Tenant `suspended` memblokir ordinary operational writes kecuali recovery/support path yang secara eksplisit diizinkan. Tenant context tidak boleh berasal dari `tenant_id` yang dikirim client tanpa server resolution.

## 9. Enforcement invariants

- Cross-tenant access adalah security incident.  
- Role ≠ official position ≠ regulatory authority.  
- Permission ≠ authority.  
- Client tidak menentukan tenant ownership.  
- Direct URL/API access menjalani authorization yang sama dengan UI.  
- Background jobs, cache, export, notifications, documents, QR projection, dan Inertia props mempertahankan tenant isolation.  
- Historical authority snapshot tidak berubah ketika official assignment kemudian berubah.

## 10. Verification contract

P0 tests mencakup tenant leakage, IDOR, tenant_id tampering, permission/authority confusion, expired authority, authority recheck, self-approval/SoD, support grant boundaries, suspended tenant writes, protected attachments, direct endpoint bypass, dan unauthorized Inertia/API data exposure.

Exact role-to-permission seed matrix dibuat pada B03; exact authority assignment behavior dibuat pada B04. Keduanya harus mengikuti kontrak ini dan RTM, bukan mengarang kewenangan dari nama role.

**RBAC & REGULATORY AUTHORITY MATRIX DESATARA v1.0 — LOCKED**
