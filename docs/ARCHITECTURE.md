# MASTER BLUEPRINT DESATARA v3.0

## Platform Pengelolaan Aset Desa

**Status:** CONTROLLED BASELINE
**Model Produk:** Multi-Desa / Multi-Tenant
**Arsitektur:** Modular Monolith
**Target:** Pemerintah Desa di Indonesia
**Backend:** Laravel
**Application Bridge:** Inertia.js
**Frontend:** Vue 3 + Tailwind CSS
**Build Tool:** Vite
**Database:** PostgreSQL
**Platform:** Responsive Web + PWA-ready
**API:** REST API versioned untuk integration/external boundaries
**Deployment:** Linux VPS / Cloud
**Bahasa Utama:** Bahasa Indonesia

---

# 1. PRODUCT VISION

DESATARA adalah platform multi-desa untuk membantu Pemerintah Desa mengelola siklus aset secara terstruktur, terdokumentasi, dapat ditelusuri, dan memiliki kontrol akses serta audit trail.

DESATARA bukan sekadar:

> CRUD daftar barang.

DESATARA harus mampu menjawab:

1. Aset apa yang dimiliki desa?
2. Dari mana aset diperoleh?
3. Berapa nilainya?
4. Di mana aset berada?
5. Siapa yang menggunakan atau bertanggung jawab?
6. Bagaimana kondisinya?
7. Dokumen apa yang mendukung kepemilikannya?
8. Apa histori aset tersebut?
9. Kapan terakhir diverifikasi secara fisik?
10. Bagaimana status administrasinya?
11. Apakah terdapat proses yang masih menunggu persetujuan?
12. Apakah data aset siap untuk pelaporan resmi?

---

# 2. REGULATORY POSITION

## 2.1 Dasar utama

DESATARA harus mengikuti sekurang-kurangnya:

- Permendagri Nomor 1 Tahun 2016 tentang Pengelolaan Aset Desa;
- Permendagri Nomor 3 Tahun 2024 tentang Perubahan atas Permendagri Nomor 1 Tahun 2016;
- Permendagri Nomor 20 Tahun 2018 tentang Pengelolaan Keuangan Desa;
- peraturan daerah dan Peraturan Bupati/Wali Kota yang relevan;
- pedoman kodefikasi aset desa yang berlaku;
- regulasi lain yang berlaku terhadap jenis/transaksi aset tertentu.

Ketentuan lama yang telah diubah atau dihapus tidak boleh diperlakukan sebagai current rule.

## 2.2 Regulatory principle

Sistem tidak boleh mengubah requirement hukum menjadi asumsi developer.

Setiap business rule yang berasal dari regulasi harus dapat ditelusuri:

REGULATION
→ ARTICLE
→ REQUIREMENT
→ BUSINESS RULE
→ MODULE
→ WORKFLOW
→ DATA
→ DOCUMENT
→ REPORT
→ TEST

## 2.3 Kedudukan DESATARA

DESATARA adalah:

**Asset Management & Governance Support Platform.**

DESATARA tidak mengklaim menggantikan aplikasi penatausahaan aset desa yang dikelola Kementerian Dalam Negeri.

Arsitektur harus memungkinkan:

- import data;
- export data;
- mapping kode;
- rekonsiliasi;
- interoperability layer;
- regulatory reporting adapter;

tanpa mengasumsikan tersedianya API resmi.

---

# 3. CORE GOVERNANCE PRINCIPLES

Sistem mengikuti prinsip:

- fungsional;
- kepastian hukum;
- transparansi;
- keterbukaan;
- efisiensi;
- akuntabilitas;
- kepastian nilai.

Prinsip teknis:

- tenant isolation;
- least privilege;
- defense in depth;
- data integrity;
- traceability;
- auditability;
- explicit workflow;
- immutable history;
- recoverability;
- regulatory traceability;
- secure by default;
- deny by default.

---

# 4. MULTI-TENANT ARCHITECTURE

Multi-desa merupakan **architectural invariant**.

Tidak boleh ditambahkan belakangan sebagai retrofit.

Model:

PLATFORM
├── Tenant Desa A
├── Tenant Desa B
├── Tenant Desa C
└── Tenant Desa N

Setiap desa merupakan tenant independen.

Data Desa A tidak boleh dapat diakses Desa B melalui:

- UI;
- URL manipulation;
- Inertia props;
- API;
- export;
- search;
- report;
- attachment;
- QR;
- background job;
- notification;
- cache;
- direct object reference.

---

# 5. TENANT MODEL

Tabel utama:

`tenants`

Field minimal:

- id
- uuid
- kode_desa
- nama_desa
- kecamatan
- kabupaten_kota
- provinsi
- alamat
- kode_pos
- logo_path
- status
- timezone
- locale
- activated_at
- suspended_at
- created_at
- updated_at

Gunakan internal primary key dan UUID/public identifier terpisah.

Jangan expose sequential database ID untuk resource publik.

---

# 6. TENANT DATA ISOLATION

Semua data tenant-owned wajib mempunyai:

`tenant_id`

Contoh:

- assets
- asset_documents
- asset_mutations
- asset_maintenances
- inventory_sessions
- reports
- approvals
- tenant memberships
- audit logs

Semua query tenant-owned harus scoped.

Konsep:

User
→ Tenant Membership
→ Active Tenant
→ Tenant Context
→ Tenant Scope
→ Authorization
→ Resource

Tidak cukup hanya menyembunyikan menu atau component Vue.

Authorization wajib dilakukan server-side.

Cross-tenant access = **security incident**.

---

# 7. PLATFORM VS TENANT ADMINISTRATION

Pisahkan dua domain.

## PLATFORM

Dikelola operator platform.

Fungsi:

- tenant provisioning;
- platform configuration;
- platform health;
- global reference data;
- feature flags;
- regulatory templates;
- support operations.

## TENANT

Dikelola masing-masing pemerintah desa.

Fungsi:

- aset;
- pengguna desa;
- lokasi;
- dokumen;
- inventarisasi;
- workflow;
- laporan;
- konfigurasi desa.

Platform administrator tidak otomatis menjadi operator aset sebuah desa.

Akses support lintas tenant, jika diperlukan, harus eksplisit, terbatas, tercatat, dan diaudit.

---

# 8. USER & MEMBERSHIP MODEL

Jangan menempelkan satu `role` langsung pada `users`.

Gunakan:

- users
- tenants
- tenant_memberships
- roles
- permissions
- role_permissions
- membership_roles

Dengan demikian satu orang secara teknis dapat:

- menjadi operator Desa A;
- auditor Desa B;
- tidak mempunyai akses Desa C.

Role selalu dievaluasi dalam konteks tenant.

---

# 9. DEFAULT ROLES

### Platform Admin

Mengelola platform, bukan otomatis mengelola aset desa.

### Tenant Admin

Administrasi aplikasi milik satu desa.

### Kepala Desa

Pemegang kewenangan sesuai workflow/regulasi.

### Sekretaris Desa

Pembantu pengelola aset desa.

### Pengurus Aset

Operasional pengelolaan aset.

### BPD / Monitoring

Akses sesuai kewenangan dan kebijakan yang berlaku.

### Auditor / Pemeriksa

Read-only terhadap ruang lingkup pemeriksaan yang diberikan.

### Viewer

Read-only terbatas.

Nama role dapat disesuaikan, tetapi permission system tetap canonical.

---

# 10. PERMISSION MODEL

Contoh:

`asset.view`

`asset.create`

`asset.update`

`asset.document.manage`

`asset.mutation.create`

`asset.maintenance.create`

`asset.inventory.execute`

`asset.disposal.request`

`asset.transfer.request`

`approval.view`

`approval.approve`

`approval.reject`

`report.view`

`report.export`

`audit.view`

`user.manage`

`tenant.settings.manage`

Permission harus diperiksa melalui server-side Policy/Gate.

Tidak boleh menjadikan:

`if ($user->role === 'kades')`

sebagai fondasi authorization.

---

# 11. REGULATORY ACTORS

Model role aplikasi harus dapat merepresentasikan kewenangan nyata.

Kepala Desa merupakan pemegang kekuasaan pengelolaan aset desa.

Sekretaris Desa berfungsi sebagai pembantu pengelola aset.

Unsur perangkat desa/pengurus aset menjalankan fungsi operasional yang ditentukan regulasi.

**Permission teknis tidak sama dengan kewenangan regulatif.**

Workflow aplikasi tidak boleh menciptakan kewenangan hukum baru hanya karena sebuah role memiliki tombol `Approve`.

Gunakan authority context/official assignment yang memiliki masa berlaku dan evidence penetapan bila diperlukan.

---

# 12. ASSET MANAGEMENT LIFECYCLE

DESATARA harus dapat mendukung:

PERENCANAAN
↓
PENGADAAN
↓
PENGGUNAAN
↓
PEMANFAATAN
↓
PENGAMANAN
↓
PEMELIHARAAN
↓
PENILAIAN
↓
PENATAUSAHAAN
↓
INVENTARISASI
↓
PELAPORAN
↓
PEMINDAHTANGANAN
↓
PENGHAPUSAN
↓
PENGAWASAN & PENGENDALIAN

Tidak semua modul harus masuk MVP, tetapi data model tidak boleh membuat implementasi tahap berikutnya mustahil.

---

# 13. PRODUCT MODULES

## Core

- Beranda
- Inventaris Aset
- Detail Aset
- Kategori/Kodefikasi
- Lokasi
- Dokumen
- Foto
- QR
- Mutasi
- Pemeliharaan
- Inventarisasi / Stock Opname
- Persetujuan
- Pelaporan
- Audit Trail
- Pengguna & Hak Akses
- Pengaturan Desa

## Extended

- Perencanaan kebutuhan
- Pengadaan reference
- Penggunaan
- Pemanfaatan
- Pengamanan
- Penilaian
- Pemindahtanganan
- Penghapusan
- Regulatory compliance
- Integrasi/interoperabilitas

---

# 14. MASTER DATA

Global reference data dan tenant-specific data harus dibedakan.

## Global

Contoh:

- referensi wilayah;
- regulatory asset classification;
- kodefikasi nasional;
- jenis transaksi standar;
- template regulasi.

## Tenant

Contoh:

- lokasi aset;
- unit/pengguna;
- konfigurasi nomor;
- pejabat;
- format internal;
- sumber referensi lokal.

Global reference tidak boleh sembarang diedit tenant.

---

# 15. CORE ASSET MODEL

Tabel:

`assets`

Minimal:

- id
- uuid
- tenant_id
- asset_category_id
- asset_subcategory_id
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
- condition
- lifecycle_status
- verification_status
- responsible_party
- notes
- created_by
- updated_by
- created_at
- updated_at

Status penggunaan formal tidak boleh direduksi menjadi satu field sederhana apabila secara regulatif membutuhkan penetapan tersendiri.

---

# 16. ASSET IDENTIFIER

Pisahkan:

- database ID;
- UUID;
- kode barang;
- nomor register;
- QR token.

Tidak satu pun boleh diasumsikan identik.

Kode barang mengikuti regulatory coding system.

Register number harus unik pada scope yang ditentukan.

QR menggunakan opaque token/UUID, bukan database ID.

---

# 17. ASSET TYPES

Model harus mampu menampung karakteristik berbeda.

Minimal:

- tanah;
- peralatan dan mesin;
- gedung/bangunan;
- jalan/infrastruktur bila relevan;
- aset tetap lainnya;
- kategori lain berdasarkan pedoman resmi.

Jangan membuat satu tabel `assets` dengan puluhan nullable columns.

Gunakan core asset + subtype detail.

Contoh:

- `asset_land_details`
- `asset_building_details`
- `asset_vehicle_details`
- `asset_equipment_details`

Subtype hanya dibuat ketika kebutuhan domain membenarkannya.

---

# 18. LAND ASSET

Data khusus tanah dapat mencakup:

- luas;
- alamat;
- penggunaan;
- status hak;
- nomor sertifikat;
- tanggal sertifikat;
- atas nama;
- batas utara;
- batas selatan;
- batas timur;
- batas barat;
- koordinat;
- dokumen kepemilikan.

Tanah desa diperlakukan sebagai high-risk asset.

Workflow pemindahtanganannya tidak boleh menggunakan workflow generik sederhana.

---

# 19. BUILDING ASSET

Dapat mencakup:

- luas bangunan;
- luas tanah;
- konstruksi;
- tahun pembangunan;
- alamat;
- koordinat;
- bukti kepemilikan;
- kondisi.

---

# 20. ACQUISITION

Pisahkan histori perolehan dari master aset.

`asset_acquisitions`

Minimal:

- tenant_id
- asset_id
- acquisition_type
- acquisition_date
- funding_source
- document_reference
- acquisition_value
- vendor/source_party
- notes

Tujuannya agar provenance aset dapat ditelusuri.

DESATARA bukan procurement engine penuh pada MVP.

---

# 21. DOCUMENT MANAGEMENT

Gunakan generic document/evidence architecture yang dapat ditautkan ke berbagai domain.

Core:

`documents`

`document_links`

Metadata minimal:

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
- created_at

Dokumen dapat ditautkan ke:

- asset;
- acquisition;
- maintenance;
- inventory;
- approval;
- valuation;
- transfer;
- disposal;
- report.

Dokumen private tidak boleh diletakkan pada public web root.

Download melalui authorization layer.

---

# 22. PHOTO MANAGEMENT

Foto merupakan evidence, bukan sekadar dekorasi.

Metadata dapat menyimpan:

- asset_id;
- tenant_id;
- type;
- captured_at;
- uploaded_by;
- checksum;
- optional coordinates;
- verification context.

Metadata lokasi tidak boleh dianggap bukti absolut tanpa verification policy.

---

# 23. QR ASSET IDENTIFICATION

QR:

QR TOKEN
→ SERVER
→ TENANT
→ ASSET
→ ACCESS POLICY
→ RESPONSE

Public QR hanya menampilkan informasi yang ditetapkan public-safe.

Contoh:

- nama aset;
- kode;
- kategori;
- status umum.

Data berikut jangan otomatis public:

- nilai;
- dokumen;
- histori audit;
- informasi pengguna;
- attachment internal.

QR token harus dapat dirotasi/revoke.

---

# 24. LOCATION MODEL

Gunakan hierarchical location.

Contoh:

Desa
└── Kompleks Kantor
　　├── Kantor Desa
　　│　├── Ruang Kepala Desa
　　│　└── Ruang Pelayanan
　　└── Gudang

Tabel:

`asset_locations`

dengan:

`parent_id`

Ini lebih fleksibel daripada string lokasi bebas.

---

# 25. ASSET CONDITION

Kondisi canonical mengikuti klasifikasi resmi yang berlaku.

Sistem tidak boleh membiarkan tenant menciptakan kondisi yang merusak reporting canonical.

Label UI dapat dikonfigurasi jika aman, tetapi canonical value tetap stabil.

---

# 26. ASSET STATUS MACHINE

Status bukan free-text.

Contoh conceptual lifecycle:

DRAFT
→ REGISTERED
→ ACTIVE

ACTIVE
→ MAINTENANCE
→ ACTIVE

ACTIVE
→ TRANSFER_PROCESS

ACTIVE
→ DISPOSAL_PROCESS
→ DISPOSED

Setiap transition memiliki:

- actor;
- permission;
- authority context bila diperlukan;
- prerequisite;
- evidence;
- approval requirement;
- audit event.

Tidak boleh langsung:

`ACTIVE → DISPOSED`

melalui edit form biasa.

---

# 27. MUTATION / MOVEMENT

Setiap perubahan lokasi/penguasaan yang relevan disimpan sebagai histori.

`asset_mutations`

Minimal:

- tenant_id
- asset_id
- mutation_type
- previous_location_id
- new_location_id
- effective_date
- reason
- document_id/reference
- status
- requested_by
- approved_by
- created_at

Current location merupakan state terbaru.

Histori tetap dipertahankan.

---

# 28. MAINTENANCE

`asset_maintenances`

Menyimpan:

- asset;
- jenis;
- tanggal;
- uraian;
- pelaksana/vendor;
- biaya;
- sumber dana;
- kondisi sebelum;
- kondisi sesudah;
- evidence;
- status.

Sistem dapat menghitung cumulative maintenance cost tanpa mengubah nilai historis transaksi.

---

# 29. USAGE

Status penggunaan aset ditangani sebagai domain tersendiri.

Gunakan konsep:

`asset_usage_determinations`

dan:

`asset_usage_items`

Sistem harus mampu mencatat dan menghasilkan dokumen/rekap yang diperlukan untuk penetapan status penggunaan sesuai periode dan ketentuan yang berlaku.

Jangan menyamakan:

`asset exists`

dengan:

`asset usage has been formally established`.

Penetapan tahun sebelumnya tidak otomatis dianggap berlaku sebagai penetapan tahun berikutnya bila ketentuan membutuhkan penetapan periodik.

---

# 30. UTILIZATION

Pemanfaatan dipisahkan dari penggunaan.

Jenis canonical mengikuti regulasi yang berlaku.

Data minimal:

- asset;
- utilization type;
- counterparty;
- start/end;
- legal/document reference;
- value/revenue jika relevan;
- approval;
- status.

Pendapatan yang timbul dapat direferensikan, tetapi DESATARA bukan general ledger.

---

# 31. SECURITY / SAFEGUARDING

Pengamanan dapat dibagi menjadi:

- administratif;
- fisik;
- hukum.

DESATARA harus mampu mencatat evidence masing-masing.

Contoh data quality flag:

`MISSING_OWNERSHIP_DOCUMENT`

`MISSING_BOUNDARY_EVIDENCE`

Flag tersebut merupakan indikator administrasi/data, bukan keputusan hukum otomatis.

---

# 32. PHYSICAL INVENTORY / STOCK OPNAME

Workflow:

CREATE INVENTORY SESSION
→ DEFINE SCOPE
→ ASSIGN TEAM
→ SCAN/SEARCH ASSET
→ VERIFY IDENTITY
→ VERIFY LOCATION
→ VERIFY CONDITION
→ CAPTURE EVIDENCE
→ RECORD DISCREPANCY
→ REVIEW
→ CLOSE
→ RECONCILIATION

Result:

- matched;
- missing;
- relocated;
- condition mismatch;
- unidentified;
- newly discovered;
- duplicate suspected.

Stock opname tidak langsung menimpa master.

Discrepancy harus melalui reconciliation.

---

# 33. INVENTORY REGULATORY SCHEDULING

Sistem harus dapat memantau kewajiban inventarisasi berdasarkan ketentuan regulasi yang berlaku.

Tenant tetap dapat melakukan inventarisasi lebih sering.

Contoh:

- annual internal verification;
- partial inventory;
- incident inventory;
- mandatory regulatory inventory.

Dashboard dapat menunjukkan status seperti:

- compliant window;
- due soon;
- overdue.

Istilah tersebut menggambarkan jadwal administratif, bukan legal verdict.

---

# 34. DISPOSAL

Tidak ada `Delete Asset` untuk menghapus histori aset administratif.

Workflow conceptual:

REQUEST
→ VALIDATION
→ CLASSIFICATION
→ REQUIRED REVIEW
→ APPROVAL
→ DOCUMENT PREPARATION
→ DECISION REGISTRATION
→ EXECUTION
→ DISPOSED

Record tetap tersedia secara historis.

Application approval harus dibedakan dari formal regulatory decision.

Hard delete hanya untuk kasus teknis sangat terbatas, misalnya draft invalid yang belum menjadi record administratif, dan tetap mengikuti authorization/audit policy.

---

# 35. TRANSFER

Pemindahtanganan merupakan domain terpisah dari mutasi internal.

Current canonical forms harus mengikuti regulasi yang berlaku.

Berdasarkan baseline regulatory matrix saat ini:

- tukar-menukar;
- penjualan.

Model lama yang memperlakukan penyertaan modal sebagai current transfer type tidak digunakan.

Khusus tanah/bangunan dan aset strategis, gunakan workflow khusus.

Jangan membuat:

`transfer_type = dropdown`

lalu langsung:

`approved = true`.

---

# 36. APPROVAL ENGINE

Gunakan reusable approval engine.

Tabel konseptual:

- approval_requests
- approval_steps
- approval_actions
- external_approvals

Setiap request menyimpan:

- tenant;
- subject type;
- subject id;
- workflow definition;
- workflow version;
- current step;
- requester;
- status;
- timestamps.

Action:

- submit;
- verify;
- approve;
- reject;
- request_revision;
- cancel jika diperbolehkan.

External approval tidak boleh direduksi menjadi approval internal.

---

# 37. WORKFLOW VERSIONING

Workflow harus versioned.

Mengubah aturan di masa depan tidak boleh mengubah arti histori approval sebelumnya.

Contoh:

`workflow_definition_version = 3`

Setiap transaksi mempertahankan versi yang digunakan ketika dibuat.

---

# 38. AUDIT TRAIL

Audit trail wajib untuk:

- login/security event penting;
- create;
- update;
- workflow transition;
- approval;
- rejection;
- export sensitif;
- document changes;
- user/role changes;
- tenant settings;
- regulatory configuration;
- platform administrative actions.

Minimal:

- tenant_id bila applicable;
- actor_id;
- event;
- subject_type;
- subject_id;
- before;
- after;
- request_id;
- IP;
- user agent;
- timestamp.

Application user tidak dapat mengedit audit log.

Audit architecture bersifat append-oriented.

---

# 39. REPORTING

Reporting dibagi menjadi:

### Operational

- daftar aset;
- kondisi;
- lokasi;
- pemeliharaan;
- mutasi;
- inventarisasi.

### Management

- nilai aset;
- kondisi per kategori;
- perubahan aset;
- outstanding actions.

### Regulatory

- buku inventaris;
- laporan aset desa;
- keputusan/status penggunaan;
- dokumen penghapusan;
- format resmi lain yang diwajibkan.

Regulatory report harus menggunakan versioned template.

---

# 40. SEMESTER REPORTING

Sistem menyediakan periode pelaporan yang dibutuhkan, termasuk:

- Semester I;
- Semester II;
- Tahun Anggaran bila relevan untuk kebutuhan internal/reporting lain.

DESATARA dapat:

- generate;
- review;
- finalize;
- freeze snapshot;
- export;
- archive.

Laporan yang telah difinalkan tidak berubah hanya karena master aset kemudian diperbarui.

Gunakan report snapshot/version.

---

# 41. OFFICIAL FORMAT ENGINE

Template laporan jangan hard-coded langsung di controller atau Vue component.

Gunakan:

- `report_templates`
- `report_template_versions`
- `report_snapshots`

Dengan metadata:

- regulation;
- effective_from;
- effective_until;
- version;
- checksum.

Domain data dapat lebih kaya daripada format laporan resmi.

Gunakan adapter:

DOMAIN DATA
→ REPORT ADAPTER
→ VERSIONED REGULATORY FORMAT

---

# 42. INTEROPERABILITY LAYER

Karena penatausahaan resmi menggunakan aplikasi yang dikelola Kemendagri, DESATARA harus mempunyai abstraction:

`OfficialAssetSystemAdapter`

Implementasi awal dapat berupa:

- export;
- import;
- mapping;
- reconciliation.

Status dapat membedakan:

- NOT_PREPARED
- READY_FOR_EXPORT
- EXPORTED
- RECONCILED

Jangan menggunakan `SYNCED` kecuali integrasi aktual benar-benar tersedia dan berhasil.

Jika kelak tersedia API resmi:

`OfficialAssetSystemApiAdapter`

Core domain tidak perlu dibongkar.

---

# 43. IMPORT

Pipeline:

UPLOAD
→ PARSE
→ MAP
→ VALIDATE
→ PREVIEW
→ DUPLICATE DETECTION
→ CONFIRM
→ IMPORT
→ AUDIT
→ ERROR REPORT

Tidak boleh:

Upload Excel → langsung INSERT.

Import harus atomic/batched sesuai skala.

Vue dapat menangani interactive mapping/preview.

Laravel tetap authoritative untuk validation dan persistence.

---

# 44. SEARCH & FILTER

Global tenant search dapat mencari:

- kode;
- register;
- nama;
- kategori;
- lokasi;
- tahun;
- sumber perolehan;
- kondisi;
- status.

Harus tenant-scoped.

Search result maupun Inertia props tidak boleh bocor lintas tenant.

Server-side filtering/pagination menjadi default untuk dataset besar.

---

# 45. DASHBOARD

Dashboard tenant menjawab:

- berapa aset;
- berapa nilai perolehan;
- kondisi;
- lokasi;
- perubahan terbaru;
- maintenance;
- pending approvals;
- stock opname;
- data incomplete;
- reporting deadline.

Dashboard bukan kumpulan chart demi chart.

Prioritasnya **decision support**.

Vue digunakan untuk interaction/visualization; aggregation dan authorization tetap dilakukan server-side.

---

# 46. DATA QUALITY

Gunakan data quality checks:

- missing identity;
- missing classification;
- missing acquisition source;
- missing location;
- missing evidence;
- invalid value;
- duplicate suspicion;
- unverified asset;
- stale verification.

Label contoh:

- Complete
- Needs Attention
- Incomplete

Jangan menyebutnya “legal compliance score” kecuali benar-benar memiliki dasar metodologi hukum yang tervalidasi.

---

# 47. REGULATORY / COMPLIANCE MATRIX

Gunakan struktur konseptual:

- regulations
- regulation_versions
- regulation_provisions
- business_rules / compliance_rules
- rule versions
- evidence requirements
- compliance results bila dibutuhkan

Tujuan:

REGULATION
→ REQUIREMENT
→ RULE
→ EVIDENCE
→ RESULT

Engine membantu pemeriksaan administratif.

Ia **tidak memberikan keputusan hukum otomatis**.

Lower-level configuration tidak boleh melemahkan mandatory national rule.

---

# 48. NOTIFICATIONS

Channel awal:

- in-app;
- email optional.

Future:

- WhatsApp adapter.

Event:

- approval pending;
- rejected;
- revision requested;
- inventory deadline;
- maintenance;
- missing document;
- reporting deadline.

Notification harus tenant-aware.

Queued notification harus membawa tenant context secara aman.

---

# 49. FILE SECURITY

File private:

`storage/app/private/...`

atau object storage private.

Akses:

REQUEST
→ AUTH
→ TENANT CHECK
→ POLICY
→ TEMPORARY/STREAMED RESPONSE

Jangan menyimpan dokumen sensitif di:

`public/uploads`.

Filename server-generated.

Validasi:

- MIME;
- extension;
- size;
- signature bila relevan;
- malware scanning jika infrastructure mendukung.

---

# 50. DATABASE STRATEGY

**PostgreSQL dikunci sebagai primary database.**

Alasan arsitektural:

- constraint kuat;
- JSONB;
- indexing;
- transaction;
- concurrency;
- advanced query capability;
- cocok untuk multi-tenant application.

Gunakan foreign key dan database constraint.

Jangan menggantungkan integritas hanya pada Laravel validation.

---

# 51. TENANCY DATABASE STRATEGY

v3.0 menggunakan:

**Shared Database + Shared Schema + `tenant_id`**

Bukan database-per-village pada fase awal.

Alasan:

- deployment sederhana;
- migration mudah;
- biaya rendah;
- cocok untuk platform multi-desa;
- reporting platform lebih mudah.

Namun seluruh tenant-owned table wajib memiliki isolation strategy.

---

# 52. UNIQUE CONSTRAINTS

Unique constraint harus tenant-aware.

Bukan:

`UNIQUE(register_number)`

tetapi jika aturan domain memungkinkan:

`UNIQUE(tenant_id, register_number)`

Hal yang sama berlaku untuk identifier tenant-specific lainnya.

Cross-tenant composite foreign key/constraint dapat dipertimbangkan untuk domain kritis guna memperkuat isolation pada database layer.

---

# 53. MONEY

Jangan gunakan float.

Gunakan:

`NUMERIC/DECIMAL`

dengan precision yang memadai.

Nilai historis transaksi tidak dihitung ulang secara destruktif.

Currency awal:

`IDR`

Tetapi representasi teknis tidak boleh bergantung pada floating-point.

---

# 54. TIME

Database timestamps menggunakan standar konsisten.

Tenant memiliki timezone configuration.

Display mengikuti timezone tenant.

Audit timestamp harus tidak ambigu.

Canonical server/database timestamps sebaiknya disimpan secara konsisten dan dikonversi pada presentation layer sesuai tenant context.

---

# 55. SOFT DELETE & IMMUTABILITY

Tidak semua tabel menggunakan strategi sama.

### Master/config

Soft delete bila tepat.

### Transaction

Prefer state/cancellation.

### Audit

Append-only.

### Finalized reports

Immutable/versioned.

### Assets

Lifecycle status, bukan delete sebagai mekanisme penghapusan administratif.

---

# 56. APPLICATION & API BOUNDARY

DESATARA menggunakan **Laravel + Inertia.js** sebagai application boundary utama web.

Alur:

Browser
→ Vue Page
→ Inertia.js
→ Laravel Route
→ Middleware
→ Controller
→ Policy / Authorization
→ Application / Domain Service
→ PostgreSQL

Web frontend internal **tidak menggunakan REST API sebagai default communication layer**.

Inertia memungkinkan Vue menjadi presentation layer tanpa memisahkan DESATARA menjadi frontend SPA dan backend REST API terpisah.

REST API disediakan untuk boundary yang memang membutuhkan API, seperti:

- integration;
- future mobile application;
- interoperability;
- machine-to-machine communication;
- external/public API yang memang dirancang demikian.

Gunakan:

`/api/v1/...`

API harus:

- authenticated sesuai kebutuhan;
- authorized;
- tenant-scoped;
- rate-limited;
- validated;
- versioned;
- audited untuk operasi sensitif.

Jangan membuat REST endpoint hanya agar frontend DESATARA sendiri dapat berbicara dengan backend DESATARA.

---

# 57. FRONTEND DECISION

Dikunci:

**Laravel + Inertia.js + Vue 3 + Tailwind CSS**

Build:

**Vite**

DESATARA **bukan frontend SPA terpisah + backend REST API terpisah**.

Arsitektur:

Laravel Modular Monolith
├── Domain / Application Layer
├── Authorization / Policies
├── Tenant Context
├── Web / Inertia Controllers
└── Vue Presentation Layer

Vue digunakan untuk:

- pages;
- reusable components;
- complex forms;
- interactive tables;
- dashboard;
- filters;
- modal/dialog;
- multi-step workflow;
- stock opname;
- QR interaction;
- import preview/mapping;
- approval interface;
- responsive navigation;
- client-side interaction.

Laravel tetap source of truth untuk:

- authentication;
- authorization;
- tenant resolution;
- validation;
- business rules;
- workflow rules;
- regulatory rules;
- persistence;
- audit;
- reporting.

Vue **bukan security boundary**.

Menyembunyikan tombol di Vue hanyalah UX.

---

# 58. MODULAR MONOLITH

Domain module:

- Tenancy
- Identity
- Asset
- Classification
- Acquisition
- Document
- Location
- Usage
- Utilization
- Safeguarding
- Maintenance
- Inventory
- Valuation
- Transfer
- Disposal
- Approval
- Reporting
- Regulation/Compliance
- Audit
- Notification
- Integration

Satu Laravel application.

Bukan microservices.

Frontend Vue berada dalam aplikasi yang sama dan dihubungkan melalui Inertia.js.

Tidak membuat repository frontend/backend terpisah tanpa kebutuhan arsitektural nyata.

---

# 59. SERVICE & PRESENTATION BOUNDARIES

Business logic tidak ditumpuk di Controller maupun Vue component.

Backend gunakan sesuai kebutuhan:

- Actions;
- Services;
- Policies;
- Form Requests;
- DTO / Value Objects;
- Domain Enums;
- Jobs;
- Events / Listeners.

Controller tetap tipis.

Model tidak menjadi dumping ground seluruh business logic.

Presentation layer:

- Inertia Pages;
- Vue Components;
- Vue Composables;
- Layouts;
- shared UI primitives.

Vue component tidak boleh mengimplementasikan ulang canonical business rule.

Frontend validation digunakan untuk UX.

Laravel server-side validation tetap authoritative.

Shared Inertia props harus minimal dan tidak boleh membocorkan:

- tenant lain;
- secrets;
- sensitive configuration;
- private document metadata yang tidak diperlukan;
- authorization context yang tidak relevan.

---

# 60. AUTHENTICATION

Minimal:

- secure password hashing;
- session authentication;
- password reset;
- session invalidation;
- login throttling;
- CSRF protection.

Future:

- MFA;
- SSO pemerintah bila tersedia.

Privileged accounts sebaiknya mendukung MFA.

Web application menggunakan Laravel session authentication sebagai baseline.

Jangan memperkenalkan JWT untuk first-party Inertia UI tanpa kebutuhan nyata.

---

# 61. AUTHORIZATION

Authorization harus terjadi melalui kombinasi:

1. route/middleware level bila sesuai;
2. tenant context;
3. Policy/Gate;
4. permission;
5. authority context bila regulatif;
6. workflow/business rule.

Vue boleh menerima data seperti:

`canApprove: true`

untuk menentukan UX.

Tetapi backend tetap wajib memverifikasi tindakan tersebut ketika request diterima.

---

# 62. SECURITY BASELINE

Wajib diuji terhadap:

- IDOR;
- broken access control;
- cross-tenant leakage;
- SQL injection;
- XSS;
- CSRF;
- mass assignment;
- insecure upload;
- privilege escalation;
- insecure direct file access;
- rate abuse;
- session issues;
- export leakage;
- unsafe Inertia shared props.

Cross-tenant penetration tests menjadi release blocker.

---

# 63. DATA PROTECTION

Collect minimum necessary data.

Sensitive information tidak masuk log secara sembarangan.

Backup harus encrypted jika infrastructure mendukung.

Secrets hanya melalui environment/secret manager.

Tidak ada secret di repository.

Frontend bundle tidak boleh berisi server secrets.

---

# 64. BACKUP & DISASTER RECOVERY

Backup:

- database;
- attachments;
- configuration yang diperlukan.

Policy minimal harus menetapkan:

- schedule;
- retention;
- offsite copy;
- encryption;
- monitoring.

Yang paling penting:

**restore drill.**

Backup belum dianggap tervalidasi sebelum proses restore pernah diuji.

---

# 65. OBSERVABILITY

Production minimal:

- application logs;
- error tracking;
- failed jobs;
- queue health;
- backup status;
- disk/storage;
- uptime;
- security events.

Log harus mengandung correlation/request ID bila relevan.

Frontend error monitoring dapat ditambahkan tanpa mengirim data sensitif yang tidak diperlukan.

---

# 66. PERFORMANCE

Baseline:

- server-side pagination;
- eager loading;
- indexes;
- no N+1;
- background jobs untuk heavy export/import;
- caching hanya pada data yang aman;
- file streaming;
- query profiling;
- lazy/deferred data loading bila relevan;
- frontend bundle discipline.

Cache key tenant-owned wajib mengandung tenant context.

Vue tidak boleh menyebabkan seluruh dataset besar dikirim ke browser hanya untuk melakukan filtering client-side.

---

# 67. PWA & OFFLINE

Arsitektur Vue harus **PWA-ready**.

Tahap awal:

- responsive;
- installable ketika PWA diaktifkan;
- camera-friendly;
- QR-scanning friendly;
- touch-friendly;
- graceful pada jaringan lambat;
- clear loading/error states.

Full offline mutation bukan bagian MVP.

Jangan melakukan offline write/synchronization sebelum tersedia strategi untuk:

- tenant context;
- authentication expiry;
- stale data;
- conflict resolution;
- duplicate operation;
- workflow state changes;
- audit timestamp;
- attachment synchronization.

Offline stock opname merupakan fase tersendiri setelah online workflow stabil.

---

# 68. ACCESSIBILITY

Target minimal:

WCAG 2.2 AA sejauh relevan.

Termasuk:

- keyboard navigation;
- visible focus;
- sufficient contrast;
- form labels;
- error messages;
- semantic structure;
- screen-reader friendly tables/actions;
- accessible dialog;
- accessible loading state.

Vue component reusable harus mempertahankan accessibility contract.

---

# 69. DESIGN SYSTEM

Karakter:

- modern;
- formal;
- minimal;
- calm;
- trustworthy;
- government-grade.

Gunakan:

- clear hierarchy;
- restrained palette;
- accessible status colors;
- consistent spacing;
- data-first layout;
- reusable component primitives.

Hindari dashboard yang berubah menjadi “festival kartu”.

UI harus terasa seperti aplikasi administrasi profesional, bukan landing page.

---

# 70. INFORMATION ARCHITECTURE

Navigasi tenant:

**Beranda**

**Aset**

- Daftar Aset
- Inventarisasi
- Mutasi
- Pemeliharaan
- Penggunaan
- Pemanfaatan
- Pemindahtanganan
- Penghapusan

**Persetujuan**

**Laporan**

**Master Data**

- Kategori/Kode
- Lokasi
- Sumber Dana/Perolehan
- Referensi lain

**Audit**

**Administrasi**

- Pengguna
- Role & Permission
- Pengaturan Desa

Menu ditampilkan berdasarkan permission.

Tetapi hidden menu bukan authorization.

---

# 71. PUBLIC AREA

Public area optional dan default minimal.

Contoh:

`/a/{opaque-token}`

Tenant dapat menentukan apakah public asset lookup aktif.

Tidak ada public directory seluruh aset secara default.

Public response menggunakan explicit allowlist field.

---

# 72. REGULATORY REPORT SNAPSHOT

Ketika laporan difinalisasi:

CURRENT DATA
→ GENERATE
→ REVIEW
→ FINALIZE
→ SNAPSHOT
→ HASH
→ EXPORT

Perubahan master setelah finalisasi tidak mengubah snapshot.

Correction menggunakan revision/version baru.

Snapshot juga mempertahankan konteks historis penting seperti pejabat/template/regulatory version yang digunakan.

---

# 73. DOCUMENT NUMBERING

Nomor dokumen configurable per tenant.

Namun template numbering tidak boleh memengaruhi canonical internal ID.

Contoh:

`001/INV/DS-CIKADU/2026`

hanya display/business identifier.

Uniqueness scope harus didefinisikan per jenis dokumen/periode/tenant sesuai requirement.

---

# 74. APPROVAL EVIDENCE

Approval menyimpan:

- actor;
- authority context;
- action;
- timestamp;
- notes;
- supporting documents;
- workflow version.

Tidak sekadar boolean:

`approved = true`.

Application approval harus dibedakan dari:

- formal decision;
- external approval;
- document registration;
- effective date.

---

# 75. CONCURRENCY

Gunakan transaction untuk operation kritis.

Pertimbangkan optimistic/pessimistic locking pada:

- approval;
- finalization;
- register allocation;
- stock reconciliation;
- disposal;
- transfer.

Double approval, duplicate submit, stale form dan lost update harus diuji.

Frontend disabled button tidak cukup untuk mencegah duplicate operation.

---

# 76. AUDIT EXPORT

Auditor dapat memperoleh export sesuai scope permission.

Export harus mencatat:

- siapa;
- tenant;
- filter;
- waktu;
- tipe laporan.

Export sensitif tidak boleh menghasilkan unrestricted permanent public URL.

Heavy export dijalankan melalui job bila diperlukan.

---

# 77. DATA PORTABILITY

Setiap tenant harus dapat memperoleh export datanya dalam format yang wajar.

Tujuan:

- backup independen;
- migrasi;
- audit;
- menghindari vendor lock-in.

Portability tidak berarti membuka data tenant lain atau internal platform secrets.

---

# 78. TENANT LIFECYCLE

Status tenant:

- pending;
- active;
- suspended;
- archived.

Suspension tidak menghapus data.

Tenant deletion harus merupakan controlled administrative process dengan retention policy.

Archived tenant tidak otomatis kehilangan historical integrity.

---

# 79. PLATFORM AUDIT

Platform actions juga diaudit.

Misalnya:

- tenant dibuat;
- tenant disuspend;
- feature diubah;
- support access diberikan;
- global template diubah;
- regulatory rule/version diubah.

Jangan hanya mengaudit user desa.

---

# 80. MVP v1.0

MVP dikunci pada:

1. Multi-tenancy
2. Authentication
3. Tenant membership
4. RBAC
5. Dashboard
6. Master data
7. Inventaris aset
8. Detail aset
9. Dokumen
10. Foto
11. QR
12. Lokasi
13. Mutasi
14. Pemeliharaan
15. Stock opname
16. Approval engine
17. Audit trail
18. Regulatory/basic reporting
19. PDF/Excel export
20. Import data
21. Backup/restore readiness
22. Security baseline

Frontend MVP menggunakan:

**Inertia.js + Vue 3 + Tailwind CSS**

Tidak ada React maupun Alpine sebagai frontend framework kedua.

---

# 81. POST-MVP

Setelah core stabil:

- full utilization;
- advanced safeguarding;
- valuation;
- transfer workflows;
- advanced disposal;
- mapping;
- PWA enhancements;
- offline inventory;
- notification integrations;
- advanced analytics;
- official-system adapter;
- district/regency oversight mode;
- future mobile client bila benar-benar diperlukan.

---

# 82. DISTRICT / REGENCY READINESS

Multi-desa tidak berarti kecamatan/kabupaten otomatis boleh membaca semua tenant.

Future oversight model harus menggunakan explicit organization scope:

Village
→ District
→ Regency

Contoh permission:

`oversight.asset.summary`

`oversight.report.view`

Bukan bypass tenant isolation.

Oversight assignment harus eksplisit dan dapat diaudit.

---

# 83. TESTING PYRAMID

Wajib:

- unit;
- feature;
- integration;
- authorization;
- tenant isolation;
- workflow;
- database constraint;
- import/export;
- report;
- file access;
- QR;
- regression.

Browser/E2E digunakan untuk critical journey.

Frontend component tests dapat digunakan untuk component yang memiliki interaction kompleks.

Server tests tetap wajib untuk business/security rules.

---

# 84. CRITICAL SECURITY TESTS

Release tidak boleh lolos bila:

- Desa A dapat membaca aset Desa B;
- Desa A dapat menebak URL attachment Desa B;
- user tanpa permission dapat approve;
- request dapat mengganti `tenant_id`;
- queued job berjalan pada tenant salah;
- cache mengembalikan data tenant lain;
- export mengandung tenant lain;
- Inertia props membocorkan data tenant lain;
- public QR membuka private fields.

Semua merupakan **P0 blockers**.

---

# 85. CRITICAL BUSINESS TESTS

Wajib membuktikan:

- kode/register uniqueness benar;
- status transition valid;
- approval tidak dapat dilewati;
- disposed asset tetap memiliki histori;
- finalized report tidak berubah;
- stock discrepancy tidak otomatis merusak master;
- audit trail tercipta;
- regulatory template menggunakan versi benar;
- formal decision tidak disamakan dengan application approval;
- workflow lama tetap mempertahankan historical version.

---

# 86. CI/CD GATES

Pipeline:

LINT
→ FORMAT CHECK
→ STATIC ANALYSIS
→ FRONTEND TYPE/QUALITY CHECK
→ UNIT
→ FEATURE
→ SECURITY TEST
→ TENANT ISOLATION
→ FRONTEND BUILD
→ MIGRATION CHECK
→ DEPLOY STAGING
→ SMOKE/E2E
→ PRODUCTION

Production tidak boleh dideploy hanya karena sebagian test hijau.

Backend dan frontend build merupakan satu release unit untuk web application DESATARA.

---

# 87. MIGRATION POLICY

Migration:

- forward-compatible sebisa mungkin;
- destructive migration membutuhkan explicit review;
- production data tidak diubah melalui manual SQL tanpa prosedur;
- schema change memiliki rollback/mitigation plan;
- perubahan tenant-owned relation harus mempertimbangkan isolation;
- perubahan regulatory/history schema harus mempertimbangkan historical integrity.

---

# 88. SEEDING

Pisahkan:

- reference seed;
- demo seed;
- test fixture/factory;
- production bootstrap.

Demo data tidak pernah otomatis masuk production.

Global regulatory/reference seed harus version-aware.

---

# 89. DEVELOPMENT ENVIRONMENT

## Backend

- PHP versi yang didukung Laravel target;
- Composer;
- Laravel;
- PostgreSQL;
- Redis optional/recommended;
- queue worker;
- private/object storage;
- mail sandbox.

## Frontend

- Node.js LTS;
- npm/pnpm sesuai project standard;
- Vite;
- Vue 3;
- Inertia.js;
- Tailwind CSS.

Development dan production build harus reproducible.

Dependency versions dikontrol melalui lock files.

Environment parity dijaga sejauh praktis.

---

# 90. DOCUMENTATION SET

Repository minimal memiliki:

`README.md`

`docs/PRD.md`

`docs/ARCHITECTURE.md`

`docs/ERD.md`

`docs/DATA-DICTIONARY.md`

`docs/RBAC.md`

`docs/WORKFLOWS.md`

`docs/REGULATORY-MATRIX.md`

`docs/SECURITY.md`

`docs/UI-UX.md`

`docs/TESTING.md`

`docs/DEPLOYMENT.md`

`docs/BACKUP-RESTORE.md`

`docs/CHANGELOG.md`

Blueprint bukan pengganti seluruh dokumen tersebut.

Blueprint adalah **source-of-truth tingkat produk/arsitektur**.

---

# 91. AI AGENT & DEVELOPMENT RULES

AI/developer wajib:

1. membaca Blueprint dan Regulatory Matrix sebelum perubahan besar;
2. menjaga tenant isolation;
3. tidak hard-code role;
4. menggunakan migration;
5. menggunakan server-side authorization;
6. memvalidasi semua input di server;
7. tidak hard-delete administrative history;
8. tidak menaruh private document di public storage;
9. menjaga auditability;
10. menambahkan test untuk behavior baru;
11. tidak mengarang ketentuan regulasi;
12. menandai ketidakpastian hukum sebagai open issue;
13. tidak mengubah finalized historical data secara destruktif;
14. tidak menurunkan security control untuk mempermudah implementasi;
15. melakukan dependency analysis sebelum perubahan schema/API;
16. menggunakan Vue 3 sebagai frontend framework utama;
17. menggunakan Inertia.js sebagai web application bridge;
18. tidak memperkenalkan React tanpa ADR baru;
19. tidak memperkenalkan Alpine.js sebagai frontend framework kedua tanpa ADR/alasan arsitektural;
20. tidak membuat REST API untuk internal UI tanpa kebutuhan boundary;
21. tidak memindahkan authorization ke Vue;
22. tidak memindahkan canonical validation ke Vue;
23. tidak memindahkan workflow/regulatory rule ke browser;
24. menjaga Inertia shared props minimal;
25. memastikan setiap tenant-owned request diverifikasi kembali oleh Laravel;
26. menjaga component reusable tetapi tidak membuat abstraction tanpa kebutuhan nyata.

---

# 92. DEFINITION OF DONE

Sebuah fitur belum selesai hanya karena UI bekerja.

DoD:

- requirement jelas;
- regulatory impact diperiksa;
- schema selesai;
- validation;
- authorization;
- tenant isolation;
- business logic;
- Vue/Inertia UI;
- responsive UX;
- audit event;
- error handling;
- loading state;
- tests;
- accessibility;
- documentation;
- migration safety;
- security review.

Untuk fitur regulatif:

- source regulation traced;
- workflow versioned bila diperlukan;
- evidence requirement dipenuhi;
- report/document impact diperiksa.

---

# 93. RELEASE BLOCKERS

P0:

- cross-tenant leak;
- authorization bypass;
- corrupting asset history;
- broken approval;
- exposure dokumen private;
- invalid regulatory reporting;
- irreversible migration tanpa mitigasi;
- backup tidak tersedia;
- restore belum pernah diverifikasi sebelum production launch;
- regulatory workflow yang diketahui bertentangan dengan current baseline;
- sensitive data leak melalui Inertia/API/public QR.

P1 harus diselesaikan atau mempunyai accepted risk yang terdokumentasi.

---

# 94. NON-GOALS v1

Tidak termasuk MVP:

- microservices;
- blockchain;
- AI menentukan legal compliance;
- full accounting system;
- full procurement engine;
- menggantikan aplikasi resmi Kemendagri;
- native Android/iOS;
- unrestricted public asset database;
- complex offline conflict synchronization;
- separate React SPA;
- frontend/backend repository split tanpa kebutuhan nyata.

---

# 95. ARCHITECTURAL DECISIONS — LOCKED

**ADR-001**
Multi-tenant sejak awal.

**ADR-002**
Shared Database + Shared Schema + `tenant_id`.

**ADR-003**
PostgreSQL sebagai primary database.

**ADR-004**
Laravel Modular Monolith.

**ADR-005**
**Laravel + Inertia.js + Vue 3 + Tailwind CSS sebagai web application stack.**

Vue merupakan presentation layer.

Inertia.js merupakan bridge antara Laravel dan Vue.

DESATARA tidak menggunakan frontend SPA terpisah dan backend REST API terpisah sebagai arsitektur default.

**ADR-006**
Permission-based RBAC scoped per tenant.

**ADR-007**
Private-by-default document storage.

**ADR-008**
Append-only audit architecture.

**ADR-009**
Versioned workflows dan regulatory templates.

**ADR-010**
DESATARA tidak menggantikan aplikasi resmi Kemendagri.

**ADR-011**
REST API digunakan hanya pada integration/external boundaries yang memang membutuhkan API.

**ADR-012**
No hard deletion of established administrative asset history.

**ADR-013**
Server-side Laravel merupakan authoritative security dan business-rule boundary; Vue tidak menjadi security boundary.

**ADR-014**
Application approval dibedakan dari regulatory/formal decision.

---

# 96. SOURCE OF TRUTH HIERARCHY

Jika terjadi konflik:

1. Peraturan perundang-undangan yang berlaku
2. Regulatory Traceability Matrix DESATARA
3. Master Blueprint
4. PRD
5. Architecture / ERD / Data Dictionary
6. Workflow Specification
7. Security Specification
8. UI/UX Specification
9. Implementation Plan
10. Source Code
11. Rendered UI

Source code tidak boleh dianggap lebih benar hanya karena sudah terlanjur dibuat.

Jika konflik ditemukan:

**STOP → IDENTIFY → TRACE SOURCE → RESOLVE → UPDATE DOCUMENTATION → IMPLEMENT**

Bukan menebak.

---

# 97. FINAL PRODUCT PRINCIPLE

DESATARA dibangun dengan prinsip:

> **Satu platform, banyak desa, tetapi setiap desa tetap memiliki batas data dan kewenangannya sendiri.**

Dan:

> **Setiap aset memiliki identitas, setiap perubahan memiliki histori, setiap tindakan memiliki kewenangan, setiap persetujuan memiliki bukti, dan setiap laporan dapat ditelusuri kembali ke sumber datanya.**

Untuk frontend:

> **Laravel owns truth. Inertia owns the application bridge. Vue owns interaction and presentation. Tailwind owns styling. PostgreSQL owns persistent relational data.**

Tidak ada keputusan business-critical yang hanya dipercayakan kepada browser.

---

# 98. STATUS BLUEPRINT

**MASTER BLUEPRINT DESATARA v3.0: FINAL ARCHITECTURE BASELINE — LOCKED**

Hal berikut dikunci:

- multi-desa sejak awal;
- shared database/shared schema tenancy;
- tenant isolation;
- PostgreSQL;
- Laravel Modular Monolith;
- Inertia.js;
- Vue 3;
- Tailwind CSS;
- Vite;
- tenant-scoped permission-based RBAC;
- regulatory authority separation;
- private document architecture;
- append-oriented audit;
- workflow versioning;
- regulatory template versioning;
- reporting snapshot;
- interoperability positioning;
- security baseline;
- MVP boundary.

## Superseded Decision

Keputusan sebelumnya:

`Laravel Blade + Tailwind CSS + Alpine.js`

dinyatakan:

**SUPERSEDED**

Canonical frontend architecture sekarang:

**Laravel + Inertia.js + Vue 3 + Tailwind CSS + Vite**

## Dokumen yang tetap terpisah

Blueprint ini **belum menggantikan** kebutuhan untuk menyelesaikan:

- ERD column-level;
- Data Dictionary;
- canonical asset classification;
- exact kodefikasi;
- exact approval matrix;
- official report field mapping;
- retention policy;
- complete integration/API contract;
- screen-level UI/UX specification;
- detailed implementation plan.

Regulatory Traceability Matrix v1.0 tetap menjadi dokumen turunan regulatif yang berlaku dan harus digunakan bersama Blueprint ini.

---

## FINAL STACK

**Application**

Laravel
↓
Inertia.js
↓
Vue 3
↓
Tailwind CSS
↓
Vite

**Data**

PostgreSQL

**Architecture**

Modular Monolith

- Multi-Tenant
- Shared Database / Shared Schema
- Tenant Isolation
- Permission-based RBAC
- Versioned Workflow
- Append-only Audit
- Private-by-default Storage

---

**MASTER BLUEPRINT DESATARA v3.0 — LOCKED**
