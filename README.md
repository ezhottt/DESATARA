# DESATARA

**Platform Pengelolaan Aset Desa**

DESATARA adalah platform open-source untuk membantu Pemerintah Desa mengelola aset secara terstruktur, dapat ditelusuri, dan sadar regulasi. Fokusnya bukan sekadar daftar barang, tetapi tenant isolation, historical integrity, evidence, kewenangan, workflow, pelaporan, dan auditability.

> **Status:** aktif dikembangkan. B00 Repository & Runtime Foundation selesai. B01 Authentication telah berjalan dan terverifikasi lokal; merge masih menunggu remote CI. DESATARA belum merupakan aplikasi production-ready.

## Kenapa DESATARA?

Pengelolaan aset desa membutuhkan lebih dari CRUD: siapa yang berwenang, bukti apa yang mendukung tindakan, bagaimana riwayat dipertahankan, dan bagaimana keputusan dapat diaudit. DESATARA dibangun dengan prinsip **deny by default**, evidence private by default, permission terpisah dari regulatory authority, serta traceability dari regulasi sampai test.

## Yang Sudah Bisa Dicoba

- Laravel 13 + Inertia.js + Vue 3 + Tailwind CSS + PostgreSQL.
- Login/logout berbasis session, throttling, forgot/reset password.
- Branding dasar DESATARA.
- Automated test, Pint, PHPStan/Larastan, dan production build sebagai quality gate lokal.

Fitur tenant, RBAC, master data, aset, inventarisasi, workflow, dan laporan masih mengikuti roadmap dan **belum boleh dianggap tersedia**.

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

| Milestone | Cakupan | Status |
| --- | --- | --- |
| B00 | Repository & Runtime Foundation | Selesai |
| B01 | Authentication Foundation | Implementasi lokal selesai; menunggu remote CI/merge |
| B02 | Tenant Boundary | Berikutnya setelah B01 lolos gate |
| B03-B06 | RBAC, authority, regulatory traceability, master data | Direncanakan |
| B07 | Asset Core + vertical slice | Target **v0.1.0-alpha** |
| B08-B16 | History, evidence, lifecycle, inventory, workflow, reporting, hardening | Direncanakan |
| B17 | Production Readiness | Target **v1.0.0** |

Target `v0.1.0-alpha` setelah B07 adalah **demo/developer preview**, bukan deklarasi production-ready.

## Dokumentasi

Kontrak teknis lengkap berada di [docs/](docs/). Mulai dari [Document Control](docs/DOCUMENT-CONTROL.md), lalu [Architecture](docs/ARCHITECTURE.md), [PRD](docs/PRD.md), [Security](docs/SECURITY.md), [Testing](docs/TESTING.md), dan [Implementation Plan](docs/IMPLEMENTATION_PLAN.md).

Dokumentasi authoritative berstatus **Controlled Baseline**: perubahan diperbolehkan melalui review yang eksplisit ketika ada regulasi baru, contradiction terverifikasi, atau kebutuhan implementasi yang dapat dibuktikan. Perubahan tidak boleh diam-diam melemahkan security, tenant isolation, authority, historical integrity, atau acceptance gate.

## Kontribusi

Baca [CONTRIBUTING.md](CONTRIBUTING.md) sebelum membuat perubahan. Bug dan feature request dapat menggunakan template issue. Perubahan arsitektur/security/regulatory contract harus menjelaskan dampak dan menyertakan pembaruan test/dokumentasi yang relevan.

## Lisensi

DESATARA menggunakan [MIT License](LICENSE).

**DESATARA — Platform Pengelolaan Aset Desa**
