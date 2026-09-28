# DESATARA

**Platform Pengelolaan Aset Desa**

DESATARA adalah platform open-source untuk membantu Pemerintah Desa di Indonesia mengelola aset desa secara aman, terstruktur, dapat ditelusuri, dan sadar regulasi. Sistem dirancang untuk menangani pencatatan aset, dokumen/evidence, inventarisasi, riwayat siklus hidup, persetujuan, pelaporan, serta audit trail melalui workflow digital yang terkendali.

> **Status proyek:** Pre-Implementation — baseline dokumentasi telah **LOCKED** dan proyek siap memasuki **B00: Repository & Runtime Foundation**.

## Mengapa DESATARA?

Pengelolaan aset desa bukan sekadar CRUD daftar barang. Data administratif membutuhkan kepemilikan yang jelas, integritas sejarah, evidence pendukung, authorization, kewenangan regulatif, serta jejak keputusan yang dapat diaudit.

DESATARA dibangun sejak awal dengan prinsip:

- isolasi multi-tenant yang ketat;
- pemisahan permission teknis dan kewenangan regulatif;
- historical integrity untuk tindakan administratif;
- evidence-first workflow;
- traceability dari regulasi hingga business rule dan test;
- rekonsiliasi inventarisasi yang terkendali;
- approval dan lifecycle operation yang dapat diaudit;
- production readiness berbasis evidence.

## Status Implementasi

Saat ini DESATARA **belum merupakan aplikasi siap deploy**. Repository berada pada fase dokumentasi pre-implementation. Requirement, architecture, workflow, data contract, security, testing, API boundary, deployment, dan design system telah dibaseline-kan sebelum implementasi dimulai.

Daftar kemampuan di bawah adalah **planned/locked requirements**, bukan klaim bahwa fitur tersebut sudah tersedia.

## Ruang Lingkup Produk

DESATARA dirancang untuk mendukung:

- administrasi desa/tenant dan membership;
- Role-Based Access Control (RBAC);
- pejabat dan regulatory authority assignment;
- regulatory traceability dan versioned business rules;
- registrasi aset dan acquisition provenance;
- klasifikasi aset berversi;
- lokasi aset hierarkis;
- riwayat penanggung jawab, kondisi, klasifikasi, dan lifecycle;
- dokumen, evidence, foto, dan QR lookup yang aman;
- penggunaan, pemanfaatan, pengamanan, pemeliharaan, dan penilaian;
- mutasi, pemindahtanganan, dan penghapusan aset;
- inventarisasi, discrepancy, dan controlled reconciliation;
- workflow dan approval yang berversi;
- formal decision dan external approval reference;
- immutable report snapshot dan revision;
- bulk import dengan validation dan preview;
- controlled export dan interoperability state;
- audit trail, dashboard, pencarian, dan responsive administration UI.

## Arsitektur

DESATARA menggunakan **modular monolith** dengan PostgreSQL shared database/shared schema dan tenant isolation eksplisit.

Prinsip arsitektur utama:

1. **Server authoritative** — Laravel memegang authentication, tenant context, authorization, authority check, validation, workflow, dan persistence.
2. **Deny by default** — akses diberikan secara eksplisit.
3. **Tenant isolation adalah P0** — cross-tenant data leakage memblokir release.
4. **Permission ≠ regulatory authority** — akses teknis tidak menggantikan kewenangan administratif/regulatif.
5. **Historical truth dipertahankan** — critical history bersifat append-oriented dan finalized record tidak ditulis ulang diam-diam.
6. **Evidence private by default** — akses file melewati application authorization boundary.
7. **Critical state change memakai explicit workflow** — bukan generic status mutation.
8. **Production readiness berbasis evidence** — test, migration, backup/restore, dan smoke check merupakan bagian acceptance.

## Target Technology Stack

| Layer | Teknologi |
| --- | --- |
| Backend | Laravel |
| Application bridge | Inertia.js |
| Frontend | Vue 3 |
| Styling | Tailwind CSS |
| Database | PostgreSQL |
| Build tooling | Vite |
| First-party authentication | Session-based |
| Architecture | Modular Monolith |
| Tenancy | Shared DB / Shared Schema + `tenant_id` |

Versi dependency konkret akan dikunci pada B00 menggunakan release stabil dan kompatibel.

First-party web menggunakan Laravel + Inertia. REST API bukan transport default internal. Boundary API berversi `/api/v1` disediakan hanya untuk integration/external/future mobile/M2M yang memang membutuhkannya.

## Dokumentasi sebagai Kontrak

DESATARA dikembangkan dengan pendekatan **documentation-first**.

Mulai dari **[Document Control & Contract Index](docs/DOCUMENT-CONTROL.md)** untuk melihat source-of-truth, contract hierarchy, precedence, conflict resolution, dan change-control.

| Dokumen | Fungsi |
| --- | --- |
| [Regulatory Traceability Matrix](docs/REGULATORY-MATRIX.md) | Traceability regulasi → requirement/business rule |
| [Architecture](docs/ARCHITECTURE.md) | System architecture, tenancy, domain boundary, invariant |
| [Product Requirements](docs/PRD.md) | Scope dan functional/non-functional requirements |
| [Business Process & Workflows](docs/WORKFLOWS.md) | State transition dan workflow behavior |
| [RBAC & Regulatory Authority](docs/RBAC.md) | Role, permission, official position, authority, SoD |
| [Entity Relationship Design](docs/ERD.md) | Logical data model |
| [Data Dictionary](docs/DATA-DICTIONARY.md) | Physical data contract dan constraints |
| [UI/UX & Information Architecture](docs/UI-UX.md) | IA, interaction, responsive dan accessibility behavior |
| [Design System](docs/DESIGN.md) | Visual language, semantic tokens, component appearance |
| [Security Specification](docs/SECURITY.md) | Security boundary, controls dan production security gates |
| [API Contract Baseline](docs/API.md) | API boundary, versioning dan protocol contract |
| [Runtime & Deployment Architecture](docs/DEPLOYMENT.md) | Runtime topology dan deployment contract |
| [Testing & Acceptance Criteria](docs/TESTING.md) | Verification, regression dan release evidence |
| [Implementation Plan](docs/IMPLEMENTATION_PLAN.md) | Dependency-ordered implementation batches |

Aturan kerja coding agent berada di **[AGENTS.md](AGENTS.md)**. File tersebut tidak menggantikan kontrak produk; ia mengarahkan agent untuk mengikuti contract hierarchy dan STOP conditions.

## Roadmap Implementasi

| Batch | Scope | Status |
| --- | --- | --- |
| B00 | Repository & Runtime Foundation | **Berikutnya** |
| B01 | Authentication Foundation | Direncanakan |
| B02 | Tenant Boundary | Direncanakan |
| B03 | RBAC | Direncanakan |
| B04 | Officials & Regulatory Authority | Direncanakan |
| B05 | Regulatory Traceability | Direncanakan |
| B06 | Master Data & Classification | Direncanakan |
| B07 | Asset Core | Direncanakan |
| B08 | Historical Asset State | Direncanakan |
| B09 | Documents, Evidence & QR | Direncanakan |
| B10 | Lifecycle Operations | Direncanakan |
| B11 | Inventory & Reconciliation | Direncanakan |
| B12 | Workflow & Approval Engine | Direncanakan |
| B13 | Reporting | Direncanakan |
| B14 | Import, Export & Interoperability | Direncanakan |
| B15 | Dashboard, Search & UX Completion | Direncanakan |
| B16 | Security, Performance & Accessibility Hardening | Direncanakan |
| B17 | Production Readiness & Release | Direncanakan |

Setiap batch mengikuti pola:

**Implement → Test → Review → Verify → Lock → Next**

## Security Model

Security merupakan bagian dari arsitektur, bukan pekerjaan tambahan menjelang launch.

Invariant prioritas tinggi meliputi:

- tidak ada cross-tenant leakage melalui read, write, search, export, attachment, cache, queue, notification, Inertia props, atau API;
- server-side authorization untuk setiap protected resource;
- permission dan regulatory authority divalidasi secara terpisah;
- private evidence storage dan authorized download;
- concurrency/idempotency protection untuk critical action;
- tenant-aware background jobs dan cache;
- opaque/revocable QR token dengan public-field allowlist;
- immutable audit/history untuk critical administrative action.

Security vulnerability sensitif tidak seharusnya dilaporkan melalui public issue. Responsible-disclosure channel akan ditentukan sebelum public production release pertama.

## API dan Interoperabilitas

API DESATARA tidak dirancang sebagai generic CRUD surface. Endpoint dibuat per use case ketika consumer dan boundary-nya jelas.

Machine-readable OpenAPI contract akan ditempatkan di `openapi/desatara-v1.yaml` ketika endpoint aktual pertama diimplementasikan. OpenAPI tidak boleh mengklaim endpoint future sebagai capability yang sudah tersedia.

DESATARA tidak mengklaim status `SYNCED` dengan sistem eksternal tanpa integrasi resmi yang benar-benar membuktikan sinkronisasi.

## Deployment

Target deployment adalah Linux VPS/cloud dengan TLS/reverse proxy, Laravel/Inertia application runtime, PostgreSQL, queue worker, scheduler, cache/session backend, private document storage, observability, dan backup target.

Provider-specific runbook, RPO/RTO, serta prosedur `BACKUP-RESTORE.md` akan dikunci pada fase yang relevan sebelum production release. Backup tidak dianggap terbukti sampai restore drill berhasil.

## Instalasi

Panduan instalasi akan ditambahkan setelah **B00 — Repository & Runtime Foundation** selesai dan diverifikasi.

Sebelum itu repository harus diperlakukan sebagai proyek pre-implementation, bukan aplikasi deployable.

## Kontribusi

DESATARA ditujukan untuk dikembangkan secara terbuka. Coding standards, local setup, issue templates, pull-request requirements, dan contribution workflow akan dilengkapi bersama implementation foundation.

Sebelum mengubah domain behavior, baca [Document Control & Contract Index](docs/DOCUMENT-CONTROL.md) dan kontrak terkait. Perubahan terhadap regulatory rule, tenant isolation, authority, workflow, historical integrity, security boundary, atau acceptance gate harus disinkronkan dengan dokumentasi authoritative dan regression test.

## Posisi Regulatif

DESATARA adalah **Asset Management & Governance Support Platform**. DESATARA tidak dimaksudkan untuk menyamar sebagai atau menggantikan sistem resmi pemerintah ketika aplikasi, prosedur, dokumen, keputusan, atau persetujuan resmi tetap diwajibkan.

Regulatory requirement dan mapping dikelola melalui Regulatory Traceability Matrix serta kontrak terkait dan harus ditinjau ketika regulasi yang berlaku berubah.

## Lisensi

DESATARA menggunakan **MIT License**. Ketentuan lengkap tersedia pada [LICENSE](LICENSE).

## Proyek

**DESATARA — Platform Pengelolaan Aset Desa**

Dikembangkan di Indonesia untuk mendukung tata kelola aset desa yang transparan, aman, dapat ditelusuri, dan dapat diaudit.
