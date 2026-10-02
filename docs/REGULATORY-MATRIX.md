# DESATARA — REGULATORY TRACEABILITY MATRIX v1.0

**Parent Document:** Master Blueprint DESATARA v3.0
**Status:** CONTROLLED BASELINE
**Scope:** Regulasi nasional inti pengelolaan aset desa
**Primary References:** Permendagri 1/2016 jo. Permendagri 3/2024

---

# 1. PURPOSE

Dokumen ini menjadi penghubung antara:

REGULASI
→ BUSINESS RULE
→ ACTOR
→ WORKFLOW
→ DATA
→ DOCUMENT
→ REPORT
→ TEST

Tujuannya memastikan fitur DESATARA tidak dibangun berdasarkan asumsi developer.

---

# 2. REGULATORY SOURCE MODEL

Setiap aturan yang diimplementasikan harus memiliki metadata:

- regulation_code
- regulation_title
- article
- paragraph
- requirement_type
- effective_from
- effective_until
- source_reference
- implementation_status
- notes

Business rule tidak boleh kehilangan referensi asalnya.

---

# 3. RTM-001 — PENGELOLA ASET DESA

**Source:** Permendagri 1/2016 Pasal 4–5

## Regulatory Requirement

Kepala Desa merupakan pemegang kekuasaan pengelolaan aset desa.

Sebagian kekuasaan dapat dikuasakan kepada perangkat desa.

Sekretaris Desa berperan sebagai pembantu pengelola aset.

Petugas/pengurus aset berasal dari unsur perangkat desa.

## DESATARA Mapping

Actor canonical:

- Kepala Desa
- Sekretaris Desa
- Pengurus Aset

Role aplikasi tambahan seperti:

- Tenant Admin
- Auditor
- Viewer

merupakan role teknis dan tidak otomatis mempunyai kewenangan regulatif.

## Business Rule

Permission teknis ≠ kewenangan hukum.

Contoh:

User memiliki permission:

`asset.disposal.review`

tidak otomatis berarti user berwenang menetapkan penghapusan.

## Required Data

`tenant_officials`

- tenant_id
- user_id
- official_type
- position
- appointment_document
- valid_from
- valid_until
- status

## Required Test

Sistem harus menolak approval regulatif apabila user tidak mempunyai authority context yang berlaku.

---

# 4. RTM-002 — SIKLUS PENGELOLAAN ASET

**Source:** Permendagri 1/2016 Pasal 7

Domain resmi meliputi:

- perencanaan;
- pengadaan;
- penggunaan;
- pemanfaatan;
- pengamanan;
- pemeliharaan;
- penghapusan;
- pemindahtanganan;
- penatausahaan;
- pelaporan;
- penilaian;
- pembinaan;
- pengawasan;
- pengendalian.

## DESATARA Mapping

Domain architecture tidak boleh hanya:

`assets`

`maintenance`

`disposal`

Arsitektur harus menyediakan boundary bagi seluruh lifecycle tersebut meskipun sebagian implementasinya post-MVP.

---

# 5. RTM-003 — PERENCANAAN

**Source:** Permendagri 1/2016 Pasal 8

## Requirement

Perencanaan aset terkait dengan dokumen perencanaan desa.

## DESATARA Mapping

Future module:

`asset_plans`

Data minimal:

- tenant
- planning_year
- planning_type
- asset_requirement
- quantity
- estimated_value
- reference_document
- status

## Design Rule

DESATARA tidak menggantikan sistem perencanaan/APB Desa.

Aset yang kemudian diperoleh dapat ditautkan kepada reference planning record.

---

# 6. RTM-004 — PENGADAAN

**Source:** Pasal 9

## Requirement

Pengadaan mengikuti prinsip dan ketentuan pengadaan barang/jasa desa.

## DESATARA Rule

DESATARA **bukan procurement engine** pada MVP.

DESATARA hanya mencatat provenance perolehan:

`asset_acquisitions`

serta reference:

- dokumen pengadaan;
- kontrak;
- BAST;
- bukti pembayaran;
- sumber dana.

---

# 7. RTM-005 — STATUS PENGGUNAAN

**Source:** Pasal 10

## Requirement

Status penggunaan aset ditetapkan setiap tahun melalui Keputusan Kepala Desa.

## DESATARA Mapping

Jangan gunakan satu field:

`usage_status = active`

sebagai satu-satunya bukti.

Gunakan:

`asset_usage_determinations`

- tenant_id
- fiscal_year
- decision_number
- decision_date
- decision_document
- approved_by
- finalized_at

dan:

`asset_usage_items`

- determination_id
- asset_id
- usage
- responsible_unit
- notes

## Workflow

DRAFT
→ REVIEW
→ FINALIZED

## Output

Keputusan Kepala Desa tentang Penetapan Status Penggunaan Aset Desa.

## Test

Tidak boleh menganggap status penggunaan tahun sebelumnya otomatis merupakan keputusan tahun berikutnya.

---

# 8. RTM-006 — PEMANFAATAN

**Source:** Pasal 11 dan ketentuan terkait

## Canonical Types

- sewa;
- pinjam pakai;
- kerja sama pemanfaatan;
- bangun guna serah;
- bangun serah guna.

## Rule

Pemanfaatan tidak boleh disamakan dengan penggunaan.

Gunakan domain:

`asset_utilizations`

## Data

- type
- asset_id
- counterparty
- start_date
- end_date
- value
- legal_basis
- village_regulation_reference
- approval_reference
- status

---

# 9. RTM-007 — EXTERNAL AUTHORITY

Beberapa proses tidak selesai hanya dengan approval internal desa.

DESATARA harus mampu merekam:

`external_approvals`

- authority_type
- authority_name
- document_number
- document_date
- document_file
- status

Contoh authority:

- Bupati/Wali Kota
- Gubernur
- instansi lain sesuai proses

## Rule

Approval engine tidak boleh mengubah external approval menjadi sekadar:

`approved_by = Kepala Desa`

---

# 10. RTM-008 — PENDAPATAN PEMANFAATAN

**Source:** Permendagri 1/2016 Pasal 18

Hasil bentuk pemanfaatan tertentu merupakan pendapatan desa dan masuk rekening kas desa.

## DESATARA Mapping

DESATARA mencatat reference transaksi:

- amount
- receipt_date
- treasury_reference
- document

Tetapi tidak menjadi general ledger.

Future integration dapat diarahkan ke sistem keuangan desa.

---

# 11. RTM-009 — PENGAMANAN

**Source:** Pasal 19

## Categories

### Administrative

- pembukuan;
- inventarisasi;
- pelaporan;
- penyimpanan dokumen kepemilikan.

### Physical

- pemagaran;
- tanda batas;
- penyimpanan;
- pemeliharaan.

### Legal

- bukti status kepemilikan.

## DESATARA Mapping

`asset_safeguards`

- asset_id
- safeguard_type
- action
- date
- evidence
- status

## Data Quality

Sistem dapat menandai:

`MISSING_OWNERSHIP_DOCUMENT`

`MISSING_BOUNDARY_EVIDENCE`

dan sejenisnya.

---

# 12. RTM-010 — TANAH

**Source:** Pasal 6 dan ketentuan tanah lainnya

Tanah membutuhkan treatment khusus.

## Required Domain

`asset_land_details`

Minimal:

- asset_id
- land_area
- ownership_status
- certificate_number
- certificate_date
- registered_owner
- address
- boundary_north
- boundary_south
- boundary_east
- boundary_west

## Rule

Sertifikat harus dapat dicatat sebagai evidence.

DESATARA tidak boleh menganggap upload dokumen berarti validitas hukum dokumen telah diverifikasi.

---

# 13. RTM-011 — BANGUNAN

Bangunan harus dapat memiliki bukti status kepemilikan.

## Mapping

`asset_building_details`

dan:

`asset_documents`

Document classification harus dapat membedakan evidence kepemilikan.

---

# 14. RTM-012 — PEMELIHARAAN

**Source:** Pasal 20

## Mapping

`asset_maintenances`

- asset_id
- maintenance_type
- date
- cost
- funding_reference
- description
- vendor
- evidence
- condition_before
- condition_after

## Rule

Maintenance tidak boleh overwrite histori sebelumnya.

---

# 15. RTM-013 — PENGHAPUSAN

**Source:** Pasal 21 dst. jo. lampiran Permendagri 3/2024

Penghapusan dapat terjadi karena antara lain:

- beralih kepemilikan;
- pemusnahan;
- sebab lain sesuai ketentuan.

## Mapping

`asset_disposals`

Jangan menggunakan:

`DELETE FROM assets`

## Workflow

REQUESTED
→ VERIFIED
→ DOCUMENT_PREPARATION
→ DECISION
→ EXECUTED
→ DISPOSED

## Evidence

Sistem harus dapat menyimpan:

- berita acara;
- keputusan Kepala Desa;
- alasan;
- evidence pendukung.

---

# 16. RTM-014 — ASET STRATEGIS

Aset strategis membutuhkan classification eksplisit.

## Data

`is_strategic`

tidak boleh ditentukan user secara bebas tanpa classification rule.

Lebih baik:

`strategic_asset_classifications`

yang menghasilkan status berdasarkan jenis aset/regulatory rule.

## Workflow

Penghapusan aset strategis dapat menggunakan template dan requirement berbeda dari aset nonstrategis.

---

# 17. RTM-015 — PEMINDAHTANGANAN

**Current Source:** Permendagri 3/2024 Pasal 25

## Canonical Forms

Pemindahtanganan sekarang:

1. Tukar Menukar
2. Penjualan

### Critical Change

Penyertaan modal tidak lagi dicantumkan sebagai bentuk pemindahtanganan dalam Pasal 25 hasil perubahan 2024.

Pasal 27 juga dihapus.

## DESATARA Rule

Enum lama:

TRANSFER
SALE
CAPITAL_PARTICIPATION

**DILARANG.**

Canonical model:

EXCHANGE
SALE

berdasarkan ketentuan yang berlaku.

---

# 18. RTM-016 — PEMINDAHTANGANAN BERDASARKAN JENIS ASET

Pasal 25 hasil perubahan membedakan:

### Tukar Menukar

Tanah dan/atau bangunan.

### Penjualan

Selain tanah/bangunan, termasuk kategori yang disebut regulasi.

## System Rule

Asset type harus menentukan permitted transaction.

Contoh:

LAND
→ SALE = BLOCK

LAND
→ EXCHANGE = potential path

tetapi tetap harus melewati seluruh requirement lanjutan.

---

# 19. RTM-017 — TANAH DESA / TUKAR MENUKAR

**Source:** Permendagri 3/2024 Pasal 32 dst.

Kategori proses:

- Proyek Strategis Nasional;
- kepentingan umum;
- bukan kepentingan umum;
- kepentingan Desa.

## Architecture Consequence

Tidak boleh satu workflow:

`LAND_EXCHANGE`

Gunakan:

`LAND_EXCHANGE_PSN`

`LAND_EXCHANGE_PUBLIC_INTEREST`

`LAND_EXCHANGE_NON_PUBLIC_INTEREST`

`LAND_EXCHANGE_VILLAGE_INTEREST`

Masing-masing memiliki versioned workflow definition.

---

# 20. RTM-018 — PENILAIAN TANAH

Beberapa transaksi tanah membutuhkan penilaian oleh pihak yang memiliki kewenangan sesuai regulasi.

## Data

`asset_valuations`

- asset_id
- purpose
- valuer_type
- valuer_identity
- valuation_date
- value
- report_number
- document
- methodology_reference

## Rule

Operator DESATARA tidak boleh mengisi sendiri nilai appraisal resmi dan menjadikannya seolah hasil penilai.

---

# 21. RTM-019 — PENATAUSAHAAN RESMI

**Current Source:** Permendagri 3/2024 Pasal 28.

## Requirement

Aset yang telah ditetapkan status penggunaannya:

- dicatat dalam buku inventaris;
- diberi kode barang.

Penatausahaan menggunakan aplikasi yang dikelola Kementerian Dalam Negeri.

## DESATARA Position

DESATARA = supporting system.

DESATARA harus memiliki:

`official_system_sync_status`

atau abstraction equivalent.

Contoh status:

- NOT_PREPARED
- READY_FOR_EXPORT
- EXPORTED
- RECONCILED

Jangan menggunakan status:

`SYNCED`

kecuali integrasi resmi benar-benar tersedia dan berhasil.

---

# 22. RTM-020 — KODE BARANG

Kode barang mengikuti pedoman umum kodefikasi aset desa.

## Rule

`asset_code` bukan free text permanen.

Arsitektur:

`asset_classifications`

- scheme
- scheme_version
- code
- name
- parent_code
- valid_from
- valid_until

Asset menyimpan:

`classification_id`

dan snapshot kode bila diperlukan untuk historical integrity.

---

# 23. RTM-021 — PELAPORAN SEMESTER

**Source:** Permendagri 3/2024 Pasal 28 ayat (3).

Kepala Desa menyampaikan laporan aset kepada Bupati/Wali Kota setiap semester.

## DESATARA Mapping

`reporting_periods`

Contoh:

2027-S1
2027-S2

`asset_reports`

- tenant_id
- reporting_period_id
- template_version
- generated_at
- reviewed_at
- finalized_at
- finalized_by
- snapshot_hash

## Reminder

Sistem dapat memberikan deadline/reminder configurable.

---

# 24. RTM-022 — INVENTARISASI

**Source:** Permendagri 3/2024 Pasal 28 ayat (4).

Inventarisasi dilakukan paling sedikit sekali dalam lima tahun.

## Rule

DESATARA harus mencatat:

`last_regulatory_inventory_at`

atau menghitungnya dari inventory sessions.

Dashboard dapat menunjukkan:

- COMPLIANT WINDOW
- DUE SOON
- OVERDUE

Tenant tetap dapat melakukan inventarisasi tahunan atau insidental.

---

# 25. RTM-023 — BUKU INVENTARIS

Format Buku Inventaris merupakan bagian dari lampiran regulasi.

## Consequence

Jangan desain export terlebih dahulu berdasarkan tabel UI.

Gunakan:

`report_template_versions`

yang mengikuti field resmi.

Internal asset schema boleh lebih kaya daripada format resmi.

Report adapter melakukan mapping:

DOMAIN DATA
→ REGULATORY FORMAT

---

# 26. RTM-024 — LAPORAN ASET DESA

Lampiran Permendagri 3/2024 menyediakan format Laporan Aset Desa.

## Rule

Report output harus versioned.

Perubahan format regulasi tidak boleh mengubah laporan historis yang telah difinalisasi.

---

# 27. RTM-025 — STATUS PENGGUNAAN DOCUMENT TEMPLATE

Lampiran juga menyediakan format Keputusan Kepala Desa tentang Penetapan Status Penggunaan Aset Desa.

## DESATARA Mapping

Template engine:

`STATUS_USAGE_DECISION`

with:

- template_version;
- regulation_reference;
- effective period.

---

# 28. RTM-026 — PENGHAPUSAN DOCUMENT TEMPLATE

Pisahkan:

- penghapusan aset strategis;
- penghapusan aset lainnya.

Template regulatory berbeda harus dipertahankan sebagai jenis dokumen berbeda.

---

# 29. RTM-027 — INVENTARISASI DAN PENILAIAN BERSAMA

**Source:** Permendagri 1/2016 Pasal 29.

Pemerintah Kabupaten/Kota bersama Pemerintah Desa melakukan inventarisasi dan penilaian sesuai ketentuan.

## Architecture

Future oversight access harus mendukung explicit scope.

Jangan memberikan Kabupaten akses langsung ke seluruh database tenant hanya karena fungsi tersebut ada.

Gunakan:

`oversight_assignments`

atau scoped access mechanism.

---

# 30. RTM-028 — REGIONAL RULE OVERRIDE

Regulasi nasional bukan satu-satunya layer.

Hierarchy:

NATIONAL
↓
PROVINCE where applicable
↓
REGENCY/CITY
↓
VILLAGE
↓
INTERNAL POLICY

## Rule

Lower-level configuration tidak boleh mengurangi mandatory national rule.

Contoh:

National:
`inventory_interval <= 5 years`

Tenant tidak boleh mengubah:

`10 years`

tetapi boleh:

`1 year`.

---

# 31. REGULATORY RULE ENGINE

Struktur:

`regulations`

`regulation_versions`

`regulation_provisions`

`business_rules`

`business_rule_versions`

`rule_evidence_requirements`

Tidak seluruh regulasi harus dieksekusi sebagai dynamic rules.

Gunakan engine hanya ketika memberikan manfaat nyata.

Critical invariants tetap dapat diimplementasikan langsung dalam domain code + test.

---

# 32. REGULATORY VERSIONING

Contoh:

Permendagri 1/2016
↓
Amended by Permendagri 3/2024
↓
Effective rule set

Jangan menjalankan Pasal lama yang sudah diubah seolah masih berlaku.

Setiap rule memiliki:

- effective_from;
- effective_until;
- supersedes_rule_id.

---

# 33. DOCUMENT EVIDENCE MODEL

Gunakan generic evidence association.

`documents`

`document_links`

Document dapat ditautkan kepada:

- asset;
- acquisition;
- maintenance;
- approval;
- disposal;
- transfer;
- inventory;
- valuation;
- report.

Hindari membuat upload architecture berbeda untuk setiap modul.

---

# 34. APPROVAL VS LEGAL DECISION

DESATARA membedakan:

### Application Approval

Tindakan user dalam workflow.

### Regulatory Decision

Dokumen/keputusan formal.

Contoh:

Kepala Desa klik approve

**tidak otomatis berarti**

Keputusan Kepala Desa telah terbit.

Workflow:

APPROVED
→ GENERATE/UPLOAD DECISION
→ REGISTER DECISION
→ EFFECTIVE

jika proses tersebut memang diwajibkan.

---

# 35. HISTORICAL INTEGRITY

Ketika:

- pejabat berubah;
- nama desa berubah;
- template berubah;
- klasifikasi berubah;
- regulasi berubah;

dokumen historis tetap harus merepresentasikan kondisi saat diterbitkan.

Gunakan snapshots.

---

# 36. TENANT REQUIREMENTS

Karena DESATARA multi-desa, setiap regulatory transaction wajib memiliki:

`tenant_id`

tetapi regulatory reference bersifat global.

Contoh:

Global:
Permendagri 3/2024

Tenant:
Desa A → Report Semester I

Tenant:
Desa B → Report Semester I

Tidak perlu menduplikasi definisi regulasi untuk setiap desa.

---

# 37. CROSS-TENANT REGULATORY SECURITY

Test wajib:

Desa A tidak dapat:

- membaca laporan Desa B;
- menggunakan decision number Desa B;
- membuka attachment Desa B;
- approve workflow Desa B;
- menggunakan inventory session Desa B;
- memasukkan asset Desa B ke report Desa A.

Semua adalah P0.

---

# 38. REGULATORY IMPLEMENTATION STATUS

Setiap requirement diberi status:

`IDENTIFIED`

`MAPPED`

`DESIGNED`

`IMPLEMENTED`

`TESTED`

`VERIFIED`

Blueprint tidak boleh menyebut sebuah regulatory requirement “compliant” hanya karena fiturnya sudah dibuat.

---

# 39. OPEN REGULATORY ITEMS

Sebelum implementation specification dikunci, masih perlu diverifikasi:

1. Pedoman umum kodefikasi aset desa yang digunakan sistem.
2. Peraturan Bupati/Wali Kota per tenant.
3. Detail field seluruh lampiran Permendagri 3/2024.
4. Retention requirement dokumen.
5. Mekanisme interoperability dengan aplikasi Kemendagri.
6. Ketentuan lokal inventarisasi/penilaian.
7. Requirement regional untuk penomoran dokumen.
8. Ketentuan pengadaan barang/jasa desa yang perlu direferensikan.

Open item tidak boleh diisi dengan asumsi.

---

# 40. RTM → ERD CONTRACT

RTM ini menetapkan kebutuhan domain berikut untuk ERD:

### Platform/Tenant

- tenants
- tenant_memberships
- tenant_officials

### Regulation

- regulations
- regulation_versions
- regulation_provisions
- business_rules

### Assets

- assets
- asset_classifications
- asset_acquisitions
- asset_locations
- asset_land_details
- asset_building_details

### Lifecycle

- asset_usage_determinations
- asset_usage_items
- asset_utilizations
- asset_safeguards
- asset_maintenances
- asset_mutations
- asset_transfers
- asset_disposals
- asset_valuations

### Inventory

- inventory_sessions
- inventory_items
- inventory_discrepancies
- inventory_reconciliations

### Governance

- approval_requests
- approval_steps
- approval_actions
- external_approvals

### Evidence

- documents
- document_links

### Reporting

- reporting_periods
- report_templates
- report_template_versions
- asset_reports
- report_snapshots

### Security

- audit_logs

---

# 41. REGULATORY ACCEPTANCE GATE

Sebelum DESATARA production:

✓ Actor mapping verified
✓ Asset lifecycle mapped
✓ Current Pasal 25 implemented
✓ Deleted Pasal 27 not implemented as current law
✓ Usage decision supported
✓ Official inventory format supported
✓ Semester reporting supported
✓ Five-year inventory requirement tracked
✓ Disposal documents supported
✓ Land exchange workflows isolated
✓ External approvals supported
✓ Code classification mapped
✓ Historical regulatory version preserved
✓ Tenant isolation tested

---

# 42. FINAL RULE

Jika terjadi konflik antara:

UI requirement
vs
database convenience
vs
business request
vs
regulation

maka developer/AI harus:

**STOP → IDENTIFY CONFLICT → TRACE SOURCE → RESOLVE REQUIREMENT → IMPLEMENT**

Bukan menebak.

---

## STATUS

**Regulatory Traceability Matrix v1.0 — BASELINE LOCKED**

Dokumen berikutnya:

**ERD & Data Dictionary DESATARA v1.0**
