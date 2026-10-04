# DESATARA

**Platform Pengelolaan Aset Desa**

DESATARA adalah platform open-source untuk membantu Pemerintah Desa mengelola aset secara terstruktur, dapat ditelusuri, dan sadar regulasi. Fokusnya bukan sekadar daftar barang, tetapi tenant isolation, historical integrity, evidence, kewenangan, workflow, pelaporan, dan auditability.

> **Status:** implementasi roadmap B00-B16 selesai dan terverifikasi lokal. B17 production-readiness tooling telah diimplementasikan; release production tetap fail-closed sampai seluruh evidence environment production terpenuhi.

## Kenapa DESATARA?

Pengelolaan aset desa membutuhkan lebih dari CRUD: siapa yang berwenang, bukti apa yang mendukung tindakan, bagaimana riwayat dipertahankan, dan bagaimana keputusan dapat diaudit. DESATARA dibangun dengan prinsip **deny by default**, evidence private by default, permission terpisah dari regulatory authority, serta traceability dari regulasi sampai test.

## Yang Sudah Bisa Dicoba

- Laravel 13 + Inertia.js + Vue 3 + Tailwind CSS + PostgreSQL.
- Login/logout berbasis session, throttling, forgot/reset password.
- Branding dasar DESATARA.
- Automated test, Pint, PHPStan/Larastan, dan production build sebagai quality gate lokal.

Tenant boundary, RBAC, authority, master data, asset core, historical state, evidence/QR, lifecycle, inventory, workflow, reporting, interoperability, dashboard/search, dan hardening telah diimplementasikan pada baseline B00-B17. Status implementasi tidak sama dengan deklarasi production-ready.

## Quick Start

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
# siapkan PostgreSQL dan sesuaikan .env
php artisan migrate
npm run build
php artisan serve
```

Untuk frontend development, jalankan `npm run dev` pada terminal terpisah.

## DESATARA dan SIPADES

SIPADES adalah Sistem Pengelolaan Aset Desa yang digunakan/dilayani oleh Ditjen Bina Pemerintahan Desa Kemendagri. DESATARA **bukan SIPADES, bukan aplikasi resmi Kemendagri, dan tidak mengklaim menggantikan kewajiban penggunaan sistem, prosedur, dokumen, atau pelaporan resmi yang berlaku**.

DESATARA diposisikan sebagai platform open-source pendukung tata kelola: eksplorasi workflow yang auditable, historical integrity, evidence, tenant isolation, dan transparansi implementasi. Interoperabilitas dengan sistem pemerintah hanya akan diklaim bila tersedia integrasi resmi yang benar-benar diimplementasikan dan diverifikasi.

## Roadmap Singkat

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
| B16 | Security, Performance & Accessibility Hardening | **Selesai - local gate GREEN** |
| B17 | Production Readiness & Release | **Readiness implementation selesai; production evidence/release gate belum lengkap** |
| B18-B25 | Product Surface Completion | **Verified locally; automated gates green** |

Target release tetap v1.0.0. Status B17 tidak boleh ditafsirkan sebagai production-ready sampai mandatory external evidence pada docs/PRODUCTION-READINESS.md terpenuhi.

## Dokumentasi

Kontrak teknis lengkap berada di [docs/](docs/). Mulai dari [Document Control](docs/DOCUMENT-CONTROL.md), lalu [Architecture](docs/ARCHITECTURE.md), [PRD](docs/PRD.md), [Security](docs/SECURITY.md), [Testing](docs/TESTING.md), dan [Implementation Plan](docs/IMPLEMENTATION_PLAN.md).

Dokumentasi authoritative berstatus **Controlled Baseline**: perubahan diperbolehkan melalui review yang eksplisit ketika ada regulasi baru, contradiction terverifikasi, atau kebutuhan implementasi yang dapat dibuktikan. Perubahan tidak boleh diam-diam melemahkan security, tenant isolation, authority, historical integrity, atau acceptance gate.

## Kontribusi

Baca [CONTRIBUTING.md](CONTRIBUTING.md) sebelum membuat perubahan. Bug dan feature request dapat menggunakan template issue. Perubahan arsitektur/security/regulatory contract harus menjelaskan dampak dan menyertakan pembaruan test/dokumentasi yang relevan.

## Lisensi

DESATARA menggunakan [MIT License](LICENSE).

**DESATARA — Platform Pengelolaan Aset Desa**
