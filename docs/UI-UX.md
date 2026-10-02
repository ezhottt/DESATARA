# UI/UX SPECIFICATION + INFORMATION ARCHITECTURE — DESATARA

**Document:** `docs/UI-UX.md`
**Version:** 1.0
**Status:** CONTROLLED BASELINE
**Product:** DESATARA — Platform Pengelolaan Aset Desa
**Parent:** Master Blueprint DESATARA v3.0
**Upstream Contracts:** PRD v1.0 · Workflow v1.0 · RBAC & Regulatory Authority Matrix v1.0 · ERD v1.0 · Data Dictionary v1.0
**Frontend:** Inertia.js + Vue 3 + Tailwind CSS
**Target:** Desktop · Tablet · Mobile · PWA-ready
**Accessibility Target:** WCAG 2.2 AA

---

# 1. PURPOSE

Dokumen ini menetapkan kontrak UI/UX dan Information Architecture DESATARA.

Tujuan utamanya:

- membuat pengelolaan aset desa mudah dipahami;
- meminimalkan kesalahan administratif;
- memperlihatkan status dan tindakan yang benar secara eksplisit;
- menjaga pemisahan tenant;
- menghormati RBAC dan regulatory authority;
- membuat workflow kompleks tetap sederhana bagi pengguna;
- menyediakan pengalaman konsisten pada desktop dan mobile;
- memastikan accessibility menjadi bagian desain sejak awal.

UI DESATARA bukan security boundary.

Laravel tetap menjadi authoritative source untuk:

- authentication;
- active tenant;
- authorization;
- regulatory authority;
- validation;
- workflow;
- business rules;
- data integrity.

---

# 2. UX PRINCIPLES

DESATARA menggunakan prinsip:

**Clear → Predictable → Traceable → Efficient → Accessible**

Setiap halaman harus menjawab:

1. Saya sedang berada di mana?
2. Data apa yang sedang saya lihat?
3. Desa/tenant mana yang aktif?
4. Apa status data ini?
5. Apa yang perlu saya lakukan?
6. Apa konsekuensi tindakan saya?
7. Apakah tindakan masih dapat dibatalkan?
8. Siapa yang terakhir melakukan perubahan?
9. Apa bukti atau riwayatnya?

---

# 3. VISUAL DIRECTION

Karakter visual:

- modern;
- minimal;
- formal;
- calm;
- trustworthy;
- government-grade;
- data-first.

DESATARA tidak menggunakan visual dashboard berlebihan.

Hindari:

- card festival;
- gradient dekoratif berlebihan;
- glassmorphism berlebihan;
- neon;
- ikon tanpa label pada tindakan penting;
- warna sebagai satu-satunya indikator status;
- animasi yang tidak memiliki fungsi;
- tabel penuh aksi kecil yang membingungkan.

---

# 4. DESIGN SYSTEM DIRECTION

Primary visual direction:

```text
Primary       : Navy / Government Blue
Secondary     : Soft Blue
Surface       : White / Neutral
Background    : Light Neutral
Success       : Green
Warning       : Amber
Danger        : Red
Info          : Blue
Text Primary  : Dark Neutral
Text Muted    : Medium Neutral
Border        : Light Neutral
```

Exact design tokens ditetapkan saat implementation design system.

Contrast harus memenuhi WCAG 2.2 AA.

---

# 5. TYPOGRAPHY

Gunakan sans-serif modern dengan keterbacaan tinggi.

Hierarchy:

```text
Display
Page Title
Section Title
Card/Panel Title
Body
Small Body
Caption
Label
```

Hierarchy dibentuk melalui:

- size;
- weight;
- spacing;

bukan terlalu banyak warna.

---

# 6. SPACING

Gunakan consistent spacing scale.

Recommended conceptual scale:

```text
4
8
12
16
20
24
32
40
48
64
```

Whitespace digunakan untuk hierarchy, bukan dekorasi.

---

# 7. BORDER RADIUS

Radius moderat.

DESATARA tidak menggunakan bentuk terlalu rounded seperti consumer/social application.

Recommended:

```text
small   6px
medium  8px
large   12px
```

---

# 8. ICONOGRAPHY

Gunakan satu icon family konsisten.

Icon:

- sederhana;
- recognizable;
- tidak menggantikan label pada critical actions.

Contoh:

```text
Aset          box/archive
Inventarisasi clipboard/check
Mutasi        arrows
Pemeliharaan  wrench
Persetujuan   check-circle
Laporan       file/chart
Audit         history/shield
Pengguna      users
Pengaturan    settings
```

---

# 9. APPLICATION SHELL

Desktop:

```text
┌──────────────────────────────────────────────────────────┐
│ Top Bar                                                  │
├───────────────┬──────────────────────────────────────────┤
│               │ Breadcrumb                               │
│ Sidebar       │                                          │
│               │ Page Header                              │
│               │                                          │
│               │ Main Content                             │
│               │                                          │
└───────────────┴──────────────────────────────────────────┘
```

Desktop sidebar persistent/collapsible.

Mobile:

```text
┌───────────────────────┐
│ Mobile Header         │
├───────────────────────┤
│ Breadcrumb / Context  │
│                       │
│ Page                  │
│                       │
└───────────────────────┘
```

Navigation menggunakan drawer/sheet.

---

# 10. GLOBAL HEADER

Header menyediakan:

- mobile navigation trigger;
- product identity;
- active tenant;
- tenant switcher bila user memiliki lebih dari satu membership;
- notification access;
- user menu.

Tenant aktif harus selalu dapat diketahui.

Critical workflow tidak boleh membuat user lupa tenant yang sedang dikelola.

---

# 11. TENANT SWITCHER

Tenant switcher hanya muncul bila relevan.

Switch:

```text
Request Tenant Switch
→ validate membership
→ resolve tenant
→ update tenant context
→ invalidate tenant-sensitive UI state/cache
→ redirect safely
```

Tenant switch tidak mempertahankan modal/form tenant sebelumnya.

Unsaved form harus mendapat warning.

---

# 12. PRIMARY INFORMATION ARCHITECTURE

Canonical sidebar:

```text
BERANDA

ASET
├── Daftar Aset
├── Inventarisasi
├── Mutasi
├── Pemeliharaan
├── Penggunaan
├── Pemanfaatan
├── Pemindahtanganan
└── Penghapusan

PERSETUJUAN

LAPORAN

MASTER DATA
├── Kategori & Kode Aset
├── Lokasi
├── Sumber Dana / Perolehan
└── Referensi

AUDIT

ADMINISTRASI
├── Pengguna
├── Role & Permission
├── Pejabat & Kewenangan
└── Pengaturan Desa
```

Menu visibility mengikuti permission.

Hidden menu **bukan authorization**.

---

# 13. NAVIGATION PRIORITY

Untuk pengguna operasional sehari-hari, prioritas:

```text
Beranda
Daftar Aset
Inventarisasi
Persetujuan
Laporan
```

Modul advanced tidak harus menjadi visual emphasis.

---

# 14. BREADCRUMB

Contoh:

```text
Aset
Aset / Detail Aset
Aset / Mutasi / Detail
Inventarisasi / Sesi 2026
Persetujuan / Permohonan #...
Laporan / Semester I 2026
```

Breadcrumb menunjukkan location hierarchy, bukan browser history.

---

# 15. PAGE HEADER

Standard page header:

```text
Breadcrumb

Page Title                  Primary Action
Description                 Secondary Action
```

Contoh:

```text
Daftar Aset                         + Tambah Aset
Kelola seluruh aset Desa Cikadu     Impor
```

Primary action maksimal satu secara visual.

---

# 16. DASHBOARD — PURPOSE

Dashboard adalah **decision-support page**, bukan kumpulan statistik dekoratif.

Dashboard menjawab:

- berapa aset tercatat;
- berapa nilai aset;
- kondisi aset;
- status verifikasi;
- inventarisasi yang sedang berjalan;
- discrepancy belum selesai;
- approval yang memerlukan tindakan;
- maintenance yang perlu perhatian;
- laporan yang mendekati deadline;
- masalah data quality.

---

# 17. DASHBOARD STRUCTURE

Recommended:

```text
[Page Header]

[Attention / Action Required]

[Ringkasan Aset]

[Status & Kondisi]

[Workflow / Persetujuan]

[Inventarisasi]

[Pelaporan]

[Aktivitas Terbaru]
```

---

# 18. ATTENTION PANEL

Prioritas tertinggi dashboard.

Contoh:

```text
Perlu Tindakan

5 aset belum terverifikasi
3 discrepancy inventarisasi belum direkonsiliasi
2 persetujuan menunggu tindakan Anda
1 laporan mendekati batas waktu
```

Setiap item actionable bila user memiliki permission.

---

# 19. SUMMARY METRICS

Metrik yang relevan:

- total aset;
- total nilai perolehan;
- aset aktif;
- aset belum terverifikasi;
- aset kondisi rusak;
- aset sedang dalam workflow.

Metrik harus menjelaskan period/scope bila diperlukan.

---

# 20. DASHBOARD FILTER

Dashboard minimal mendukung:

- periode;
- kategori;
- lokasi;

sesuai permission dan data volume.

Filter tenant tidak diperlukan karena active tenant merupakan security context.

---

# 21. ASSET LIST PAGE

Desktop:

```text
Daftar Aset                         + Tambah Aset

[Search................] [Filter] [Import] [Export]

┌──────────────────────────────────────────────────────────┐
│ Kode │ Nama │ Kategori │ Lokasi │ Kondisi │ Status │ ⋮ │
├──────────────────────────────────────────────────────────┤
│ ...                                                      │
└──────────────────────────────────────────────────────────┘

Showing 1–25 of ...
                         Pagination
```

Server-side pagination mandatory.

---

# 22. ASSET SEARCH

Search targets:

- nama aset;
- kode aset;
- nomor register;
- identifiers yang memang searchable.

Search:

- debounced;
- server-side;
- tenant-scoped.

No full dataset download to browser for filtering.

---

# 23. FILTER PATTERN

Filter panel:

```text
Kategori
Lokasi
Kondisi
Status
Tahun Perolehan
Sumber Dana
Status Verifikasi
```

UI harus memperlihatkan active filters.

Example:

```text
Filter aktif:
[Kondisi: Rusak] [Lokasi: Kantor Desa] [× Reset]
```

---

# 24. TABLE DESIGN

Desktop table:

- sticky header bila membantu;
- horizontal overflow only when unavoidable;
- sortable columns only where server supports it;
- row click optional;
- explicit detail action;
- no excessive action icons.

Primary table actions melalui:

```text
⋮
```

atau contextual action menu.

Critical destructive/workflow action tidak boleh tersembunyi tanpa confirmation.

---

# 25. MOBILE ASSET LIST

Mobile tidak memaksakan desktop table.

Gunakan compact record list:

```text
─────────────────────────
Laptop ASUS
02.03.01.001

Kantor Desa
Baik · Aktif

Lihat Detail            >
─────────────────────────
```

Filter dibuka melalui bottom sheet/full-screen panel.

---

# 26. EMPTY STATE

Empty state harus menjelaskan:

- apa yang kosong;
- mengapa mungkin kosong;
- tindakan berikutnya.

Example:

```text
Belum ada aset

Data aset desa belum tersedia.
Tambahkan aset pertama atau impor data yang sudah ada.

[Tambah Aset] [Impor Data]
```

CTA hanya muncul bila authorized.

---

# 27. NO SEARCH RESULT

Berbeda dari empty dataset.

```text
Tidak ada aset yang cocok

Coba ubah kata pencarian atau hapus beberapa filter.

[Reset Filter]
```

---

# 28. ASSET DETAIL

Desktop structure:

```text
[Asset Header]

[Summary / Critical Status]

Tabs:
Ringkasan
Identitas
Lokasi & Penanggung Jawab
Dokumen
Riwayat
Pemeliharaan
Inventarisasi
Workflow
Audit
```

Tabs muncul sesuai permission/relevance.

---

# 29. ASSET HEADER

Example:

```text
Laptop ASUS ExpertBook

Kode: 02.03.01.001
Register: 00012

[Baik] [Aktif] [Terverifikasi]

Edit
⋮
```

Critical identifiers mudah ditemukan.

---

# 30. ASSET SUMMARY

Summary tidak menampilkan seluruh database field.

Prioritas:

- kategori;
- nilai;
- tahun/perolehan;
- lokasi saat ini;
- penanggung jawab;
- kondisi;
- lifecycle;
- verification;
- latest inventory.

---

# 31. DATA QUALITY INDICATOR

Canonical UI:

```text
● Lengkap
● Perlu Perhatian
● Belum Lengkap
```

Warna didampingi icon/text.

Click/expand menjelaskan missing requirement.

Example:

```text
Perlu Perhatian

• Dokumen bukti perolehan belum tersedia
• Lokasi belum diverifikasi
```

---

# 32. ASSET HISTORY

History menggunakan chronological timeline.

```text
28 Sep 2026
Lokasi berubah
Kantor Desa → Gudang Desa
Oleh: ...

25 Sep 2026
Pemeliharaan selesai
...
```

History bersumber dari authoritative event/history records.

---

# 33. CREATE ASSET FLOW

Jangan gunakan satu form raksasa.

Recommended stepper:

```text
1 Identitas
2 Klasifikasi
3 Perolehan
4 Lokasi & Penanggung Jawab
5 Detail Jenis Aset
6 Dokumen
7 Review
```

Draft dapat disimpan bila business rule mengizinkan.

---

# 34. FORM PRINCIPLES

Form:

- label selalu terlihat;
- placeholder bukan label;
- required jelas;
- help text dekat field;
- error dekat field;
- validation server authoritative;
- client validation untuk UX only;
- keyboard friendly;
- preserve safe input after validation error.

---

# 35. FORM ERROR SUMMARY

Untuk form panjang:

```text
Data belum dapat disimpan

3 bagian perlu diperbaiki:
• Kode aset
• Nilai perolehan
• Lokasi

[Lihat kesalahan pertama]
```

Field error tetap ditampilkan inline.

---

# 36. MONEY INPUT

User memasukkan nilai secara manusiawi.

Display:

```text
Rp 12.500.000
```

Server menyimpan numeric canonical value.

Formatting browser tidak boleh mengubah precision.

---

# 37. DATE INPUT

Gunakan locale-friendly display.

Stored authoritative value tetap date/timestamp sesuai Data Dictionary.

Hindari ambiguous:

```text
09/10/26
```

Preferred display:

```text
9 Oktober 2026
```

---

# 38. LOCATION SELECTOR

Hierarchical location selector:

```text
Kantor Desa
└── Lantai 1
    └── Ruang Pelayanan
```

Large hierarchy menggunakan searchable tree/combobox.

---

# 39. DOCUMENT UPLOAD

Upload UI memperlihatkan:

- allowed format;
- max size;
- upload progress;
- validation;
- scan state bila tersedia;
- document type;
- optional number/date where relevant.

Example:

```text
Bukti Perolehan

PDF, JPG, PNG · Maks. 10 MB

[Choose File]
```

Exact size remains configuration.

---

# 40. DOCUMENT STATE

Possible presentation:

```text
Mengunggah...
Diproses...
Siap
Gagal
Ditolak
```

File tidak dianggap usable sebelum server validation selesai.

---

# 41. DOCUMENT DOWNLOAD

Download action selalu request authorized endpoint.

UI tidak menyimpan direct public storage URL.

---

# 42. QR UI

Asset detail:

```text
QR Aset

Status: Aktif
Diterbitkan: ...
Terakhir dirotasi: ...

[Lihat QR]
[Cetak]
[Rotasi Token]
```

Rotation requires confirmation.

---

# 43. MUTATION LIST

Columns:

```text
Tanggal
Aset
Lokasi Asal
Lokasi Tujuan
Status
Pemohon
```

Status visible.

---

# 44. CREATE MUTATION

Form:

```text
Aset
Lokasi Saat Ini     read-only
Lokasi Tujuan
Tanggal Efektif
Alasan
Dokumen Pendukung
```

Review before submit.

---

# 45. MUTATION DETAIL

Must show:

- origin;
- destination;
- asset;
- reason;
- evidence;
- workflow;
- approval;
- execution;
- audit timeline.

Location master changes only after valid execution.

---

# 46. MAINTENANCE PAGE

Views:

```text
Semua
Direncanakan
Berjalan
Selesai
Perlu Perhatian
```

Maintenance may be planned or recorded after-the-fact according to permission/business rule.

---

# 47. INVENTORY LIST

Page:

```text
Inventarisasi

[+ Mulai Inventarisasi]

Aktif
─────────────────────────
Inventarisasi 2026
324 / 512 aset diverifikasi
63%
─────────────────────────

Riwayat
...
```

---

# 48. CREATE INVENTORY SESSION

Steps:

```text
1 Informasi
2 Ruang Lingkup
3 Tim
4 Review
5 Mulai
```

Before start, UI explains snapshot/reference date.

---

# 49. INVENTORY SESSION DASHBOARD

Must answer:

- total scope;
- verified;
- matched;
- discrepancy;
- missing;
- relocated;
- condition mismatch;
- newly discovered;
- unresolved;
- progress.

---

# 50. INVENTORY SCANNING

Mobile-first.

```text
[ Scan QR ]

atau

Cari kode/nama aset
```

After scan:

```text
Laptop ASUS
Kode ...
Expected:
Kantor Desa · Baik

Observed:
[Lokasi]
[Kondisi]

[Tambah Foto]
[Catatan]

[Konfirmasi]
```

---

# 51. INVENTORY RESULT

Possible result labels:

```text
Sesuai
Tidak Ditemukan
Berpindah Lokasi
Kondisi Berbeda
Objek Tidak Dikenal
Aset Baru Terindikasi
Duplikasi Terindikasi
```

Label merupakan observation/result, bukan otomatis legal conclusion.

---

# 52. DISCREPANCY UI

Discrepancy detail:

```text
Expected
vs
Observed

Evidence

Recommended resolution

Review
Reconcile
```

Never provide direct "Update Master" shortcut.

---

# 53. RECONCILIATION UI

User must see:

```text
Temuan
Bukti
Master saat ini
Tindakan yang akan dibuat
Dampak
Authority requirement
```

Confirmation required before execution.

---

# 54. INVENTORY FINALIZATION

Finalization dialog must explicitly state:

```text
Finalisasi Inventarisasi

Setelah difinalisasi, snapshot inventarisasi tidak dapat diubah.

Temuan yang belum diselesaikan: 0
Rekonsiliasi selesai: 14

[Batalkan] [Finalisasi]
```

Button disabled when blocking conditions remain.

---

# 55. APPROVAL INBOX

Primary views:

```text
Menunggu Tindakan Saya
Diajukan oleh Saya
Semua yang Dapat Dilihat
Selesai
```

Default should emphasize actionable approvals.

---

# 56. APPROVAL ROW

Show:

```text
Jenis
Subjek
Pemohon
Tanggal
Tahap
Status
Age / SLA if configured
```

Avoid exposing irrelevant internal workflow implementation.

---

# 57. APPROVAL DETAIL

Recommended layout:

```text
Request Summary

Subject Data

Evidence

Workflow Timeline

Authority Context

Decision Panel
```

---

# 58. APPROVAL DECISION PANEL

Actions only when valid:

```text
Setujui
Minta Revisi
Tolak
```

Critical actions require deliberate confirmation.

Reject/revision normally require reason.

---

# 59. AUTHORITY UX

Permission alone does not imply authority.

If permission exists but legal/administrative authority is missing:

```text
Tindakan tidak tersedia

Akun Anda memiliki akses ke modul ini, tetapi tidak memiliki
kewenangan aktif yang diperlukan untuk melakukan persetujuan.
```

Do not silently hide the reason where explanation is useful.

---

# 60. STALE APPROVAL

If resource changed after user opened page:

```text
Data telah berubah

Permohonan ini diperbarui setelah halaman dibuka.
Muat ulang data sebelum mengambil keputusan.

[Muat Ulang]
```

No silent overwrite.

---

# 61. SEGREGATION OF DUTIES

If self-approval forbidden:

```text
Anda tidak dapat menyetujui permohonan ini karena Anda adalah pengaju.
```

UI reflects server decision.

---

# 62. EXTERNAL APPROVAL UI

Must visually distinguish:

```text
Persetujuan Internal DESATARA
```

from:

```text
Persetujuan/Keputusan Eksternal
```

Never present application approval as formal external decision.

---

# 63. TRANSFER WORKFLOW UI

Transfer page must emphasize:

- type;
- affected assets;
- authority requirement;
- evidence;
- decision;
- execution status.

No generic delete action.

---

# 64. DISPOSAL WORKFLOW UI

Disposal must never look like "Delete Asset."

Use terminology:

```text
Usulan Penghapusan
Validasi
Persetujuan
Keputusan
Pelaksanaan
Selesai
```

Asset remains available in historical views.

---

# 65. REPORT LIST

Views:

```text
Perlu Disiapkan
Draft
Dalam Review
Final
Arsip
```

Report cards/table should expose:

- period;
- type;
- status;
- revision;
- finalized date.

---

# 66. REPORT GENERATION

Flow:

```text
Select Period
→ Validate Data Readiness
→ Generate Draft
→ Review
→ Finalize
→ Snapshot
→ Export
```

---

# 67. REPORT READINESS

Before generation/finalization:

```text
Kesiapan Laporan

✓ 512 aset valid
✓ Periode tersedia
✓ Pejabat aktif tersedia
! 4 aset membutuhkan perhatian
```

Blocking vs warning must be visually different.

---

# 68. FINALIZED REPORT UI

Final report shows:

```text
FINAL

Semester I 2026
Revision 1

Finalized: ...
Template: ...
Checksum: ...
```

No Edit button.

Available actions:

```text
View
Download
Create Revision
```

subject to permission.

---

# 69. IMPORT UX

Wizard:

```text
1 Upload
2 Mapping
3 Validasi
4 Preview
5 Konfirmasi
6 Hasil
```

No direct import immediately after upload.

---

# 70. IMPORT PREVIEW

Display:

```text
Total rows       500
Valid            482
Warning           12
Invalid            6
Potential duplicate 4
```

User can inspect error rows before confirmation.

---

# 71. EXPORT UX

Sensitive exports require:

- explicit action;
- permission;
- scope visibility;
- audit.

Large export may be asynchronous.

Example:

```text
Ekspor sedang diproses.

Anda akan menerima notifikasi ketika file siap.
```

---

# 72. MASTER DATA UX

Master pages use consistent pattern:

```text
Search
Filter
Table/List
Create/Edit Drawer or Modal
```

But complex hierarchy such as locations/classifications may use dedicated page.

---

# 73. CLASSIFICATION TREE

Recommended:

```text
Tanah
├── Tanah Persil
└── ...

Peralatan dan Mesin
├── Kendaraan
└── ...
```

Version/effective context visible to admin.

---

# 74. LOCATION TREE

Support:

- expand/collapse;
- search;
- status;
- parent;
- asset count where useful.

Do not allow parent cycle.

---

# 75. USER MANAGEMENT

User list distinguishes:

```text
User Identity
Membership
Role
Official Position
Authority
```

These concepts must not visually collapse into one field.

---

# 76. USER DETAIL

Recommended sections:

```text
Profil
Membership
Role & Permission
Jabatan Resmi
Kewenangan
Aktivitas
```

---

# 77. ROLE MANAGEMENT

Role UI explains:

> Role merupakan kumpulan permission dan tidak otomatis menetapkan kewenangan administratif/regulatif.

System roles may be protected from destructive modification.

---

# 78. OFFICIAL & AUTHORITY MANAGEMENT

Show:

```text
Pejabat
Jabatan
Periode
Status
Bukti Pengangkatan
Kewenangan
```

Expired authority clearly marked.

---

# 79. AUDIT LOG PAGE

Filters:

```text
Tanggal
Pengguna
Event
Domain
Resource
```

Display:

```text
Time
Actor
Action
Subject
Result
```

Detail can show before/after safely.

Sensitive values must be redacted.

---

# 80. ACTIVITY TIMELINE

Use human-readable language.

Prefer:

```text
Reza mengubah lokasi aset dari Kantor Desa menjadi Gudang Desa.
```

instead of:

```text
asset_location_id changed 12 → 18
```

Technical data may remain available in audit detail.

---

# 81. NOTIFICATIONS

Notifications prioritized:

```text
Action Required
Warning
Information
Completed
```

Notification never substitutes authoritative workflow status.

---

# 82. TOAST

Toast only for short feedback:

```text
Aset berhasil disimpan.
```

Do not use toast as sole representation of critical error.

---

# 83. ERROR STATE

Generic safe error:

```text
Terjadi kesalahan

Permintaan belum dapat diproses.
Coba kembali atau hubungi administrator jika masalah berlanjut.

Reference: ...
```

Never expose stack trace/database error.

---

# 84. 403

```text
Akses tidak tersedia

Anda tidak memiliki izin untuk membuka halaman ini.
```

Do not disclose protected resource details.

---

# 85. 404

```text
Data tidak ditemukan

Data mungkin tidak tersedia atau Anda tidak memiliki akses.
```

Where security requires indistinguishability, use safe resource-not-found behavior.

---

# 86. 409 CONFLICT

```text
Data telah berubah

Pengguna atau proses lain telah memperbarui data ini.

[Muat Versi Terbaru]
```

Used for optimistic locking/concurrency conflict.

---

# 87. 422 VALIDATION

Inline field errors + summary for complex forms.

Input safe to preserve should remain.

---

# 88. 429 RATE LIMIT

```text
Terlalu banyak permintaan

Tunggu beberapa saat sebelum mencoba kembali.
```

---

# 89. LOADING

Use:

- skeleton for content;
- spinner inside action button;
- progress for long operation.

Avoid blocking whole application unnecessarily.

---

# 90. BUTTON BUSY STATE

On submit:

```text
[Simpan]
↓
[Menyimpan...]
```

Prevent accidental double submit.

Server idempotency remains required.

---

# 91. CONFIRMATION LEVELS

### Level 1

Low risk:

No confirmation.

### Level 2

Meaningful state change:

Confirmation dialog.

### Level 3

High-risk/irreversible:

Explicit consequences + confirmation.

Examples:

- finalizing inventory;
- rotating QR;
- executing transfer;
- executing disposal;
- finalizing report;
- revoking authority.

---

# 92. DANGEROUS ACTION DESIGN

Danger color reserved for actual destructive/high-risk actions.

Do not make routine "Batal" red.

---

# 93. RESPONSIVE BREAKPOINT BEHAVIOR

Conceptual:

```text
Mobile      < 640
Tablet      640–1023
Desktop     >= 1024
```

Exact Tailwind implementation may follow framework tokens.

Responsive means component adaptation, not simply shrinking.

---

# 94. MOBILE PRIORITY

Mobile must be first-class for:

- QR scanning;
- physical inventory;
- asset lookup;
- photo evidence;
- approval review;
- quick asset detail.

Complex administration may remain desktop-optimized but functional.

---

# 95. MOBILE FORMS

Use:

- one-column layout;
- appropriate keyboard type;
- large touch targets;
- sticky action footer where useful;
- native camera/file capabilities when appropriate.

---

# 96. MOBILE TABLE POLICY

Never rely solely on horizontal-scroll desktop tables for core operational tasks.

Convert important records to list/card rows.

---

# 97. ACCESSIBILITY

Minimum requirements:

- keyboard navigation;
- visible focus;
- semantic HTML;
- associated form labels;
- screen-reader names;
- sufficient contrast;
- no color-only meaning;
- accessible dialogs;
- accessible tables;
- accessible errors;
- minimum practical touch target;
- reduced-motion respect.

---

# 98. MODAL ACCESSIBILITY

Modal must:

- move focus inside;
- trap focus;
- expose title;
- support Escape where safe;
- restore focus on close;
- prevent background interaction.

High-risk confirmation may intentionally require explicit button.

---

# 99. KEYBOARD SUPPORT

Interactive elements reachable using Tab.

No clickable `<div>` as replacement for semantic button/link without accessibility implementation.

---

# 100. PWA-READY UX

Architecture may support installable PWA later.

MVP does not promise unrestricted offline mutation.

When offline capability eventually exists, UI must clearly distinguish:

```text
Saved locally
Pending synchronization
Synchronized
Conflict
```

Never imply server success before synchronization.

---

# 101. PERMISSION-BASED UI

Server supplies permitted capabilities/context.

Frontend may use them to control presentation.

Example conceptual props:

```text
can.view
can.create
can.update
can.submit
can.approve
can.finalize
```

But server repeats authorization for every action.

---

# 102. AUTHORITY-BASED UI

Permission and authority shown separately.

Example:

```text
can.approve = true
has_required_authority = false
```

Result:

button unavailable with explanatory context.

Server remains authoritative.

---

# 103. INERTIA PROP SECURITY

Never send unnecessary sensitive data simply because Vue component hides it.

Authorization must occur before serialization into Inertia props.

---

# 104. FORM DIRTY STATE

When leaving modified form:

```text
Perubahan belum disimpan

Jika keluar sekarang, perubahan akan hilang.

[Tetap di Halaman] [Keluar]
```

---

# 105. AUTOSAVE

Do not introduce autosave into regulatory/critical workflows by default.

Explicit Save Draft is preferred.

---

# 106. DATA REFRESH

Workflow pages should support intentional refresh.

Critical decision page may detect stale version before execution.

No silent replacement of user-visible critical data.

---

# 107. URL DESIGN

Human-readable resource routes.

Conceptual:

```text
/assets
/assets/{uuid}

/inventory
/inventory/{uuid}

/approvals
/approvals/{uuid}

/reports
/reports/{uuid}
```

Avoid exposing sequential IDs publicly where UUID is available.

---

# 108. QUERY STRING

Filters/search/pagination should use URL query where practical:

```text
/assets?q=laptop&condition=good&page=2
```

Benefits:

- refresh-safe;
- shareable internally;
- browser history;
- predictable navigation.

---

# 109. BACK BUTTON

Browser Back must behave naturally.

Avoid application patterns that trap navigation unnecessarily.

---

# 110. PRINT

Dedicated print layout for documents/reports.

Do not simply print application shell.

---

# 111. DARK MODE

**Dark mode is not part of DESATARA MVP.**

Reason:

- DESATARA is primarily administrative/data-entry software;
- consistency and accessibility are higher priorities;
- doubles visual QA surface;
- printed/government documents remain light;
- no functional requirement currently depends on it.

Architecture/design tokens should avoid making future dark mode impossible.

Dark mode may be evaluated post-MVP based on actual user demand.

---

# 112. GLOBAL COMMAND/SEARCH

MVP uses focused asset search.

A universal command palette/global search is not required.

Can be evaluated post-MVP after search usage is understood.

---

# 113. BULK ACTIONS

Bulk action only where:

- permission allows;
- domain operation is safe;
- server validates each resource;
- tenant scope guaranteed;
- audit exists.

No generic bulk delete.

---

# 114. UNSAFE UI ANTI-PATTERNS

Forbidden:

```text
Client-only authorization
Client-controlled tenant_id
Direct state dropdown for critical workflow
Generic Delete on established asset
Update Master from discrepancy
Editable finalized report
Approval without authority check
Public document URL
Hidden button as security
Silent stale overwrite
```

---

# 115. UI PERFORMANCE

Targets:

- server pagination;
- deferred/lazy props where useful;
- avoid huge Inertia payload;
- debounce search;
- avoid N+1 backend query;
- async heavy exports;
- optimized images/thumbnails;
- component-level loading.

---

# 116. PAGE TITLE

Browser title pattern:

```text
{Page} — DESATARA
```

Optionally tenant-aware:

```text
Daftar Aset — Desa Cikadu — DESATARA
```

No sensitive record data in title unnecessarily.

---

# 117. STANDARD COMPONENT INVENTORY

Initial reusable components:

```text
AppShell
Sidebar
Topbar
TenantSwitcher
Breadcrumb
PageHeader

Button
IconButton
Badge
Alert
Tooltip
Dropdown
Modal
Drawer
Tabs

TextInput
Textarea
Select
Combobox
DateInput
MoneyInput
FileUpload

DataTable
MobileRecordList
Pagination
FilterPanel
SearchInput

StatusBadge
DataQualityIndicator
Timeline
EmptyState
ErrorState
Skeleton

WorkflowTimeline
ApprovalPanel
AuthorityIndicator

AssetSummary
DocumentList
QRPanel

InventoryProgress
DiscrepancyComparison
ReconciliationPanel
```

Do not create abstraction before actual repeated use.

---

# 118. STANDARD STATUS COMPONENT

One central mapping should control:

- label;
- icon;
- semantic style.

Do not manually map status colors independently across pages.

---

# 119. UX COPY

Language:

- Bahasa Indonesia;
- concise;
- human-readable;
- consistent terminology.

Prefer:

```text
Simpan Perubahan
Ajukan Persetujuan
Minta Revisi
Finalisasi Laporan
```

Avoid:

```text
Submit Data
Execute Action
Process Item
```

unless technical context requires it.

---

# 120. TERMINOLOGY LOCK

Canonical UI terms:

```text
Aset
Inventarisasi
Mutasi
Pemeliharaan
Penggunaan
Pemanfaatan
Pemindahtanganan
Penghapusan
Persetujuan
Laporan
Lokasi
Penanggung Jawab
Pejabat
Kewenangan
Bukti/Dokumen
Rekonsiliasi
Finalisasi
```

Terminology must follow regulatory/domain meaning.

---

# 121. ROLE-SPECIFIC HOME EXPERIENCE

Dashboard content may adapt by permission.

### Pengurus Aset

Prioritize:

- asset completeness;
- inventory;
- discrepancies;
- maintenance;
- reporting.

### Kepala Desa / Authority Actor

Prioritize:

- pending decisions;
- approvals;
- report finalization;
- critical discrepancies.

### Auditor/Monitoring

Prioritize:

- read-only evidence;
- reports;
- audit;
- history.

### Tenant Admin

Prioritize:

- membership;
- configuration;
- data quality;
- operational health.

Role adaptation never bypasses permissions.

---

# 122. PLATFORM ADMIN UX

Platform Admin has separate platform context.

It must not visually imply automatic operational control over tenant data.

Cross-tenant support access, when authorized, must show prominent context:

```text
MODE DUKUNGAN

Anda sedang mengakses tenant:
Desa ...

Alasan:
...

Berlaku sampai:
...
```

All support activity audited.

---

# 123. TENANT SUSPENSION UX

When tenant suspended:

```text
Operasional tenant sedang ditangguhkan

Data tetap dapat tersedia sesuai kebijakan akses,
tetapi perubahan operasional tidak dapat dilakukan.
```

Allowed recovery/support actions remain explicit.

---

# 124. AUDITABILITY IN UI

Critical record detail should expose:

```text
Dibuat oleh
Dibuat pada
Terakhir diperbarui
Status
Workflow
Riwayat
```

where permission permits.

---

# 125. DATA PROVENANCE UX

Where relevant:

```text
Sumber:
• Input manual
• Impor
• Inventarisasi
• Rekonsiliasi
• Workflow
```

Users should be able to understand why authoritative data changed.

---

# 126. FIRST-RUN EXPERIENCE

Tenant with no assets:

```text
Selamat datang di DESATARA

Mulai dengan menyiapkan:
1. Profil Desa
2. Pengguna & Kewenangan
3. Master Lokasi
4. Klasifikasi
5. Data Aset

[Mulai Penyiapan]
```

Do not force tutorial overlays across application.

---

# 127. HELP

Contextual help preferred over large generic manual.

Examples:

```text
Apa itu rekonsiliasi?
Mengapa laporan tidak dapat difinalisasi?
Apa perbedaan role dan kewenangan?
```

Help text cannot invent regulatory interpretation.

---

# 128. ANALYTICS

Product analytics, if later enabled, must:

- avoid unnecessary sensitive payload;
- avoid asset/document contents;
- respect privacy;
- distinguish tenant safely;
- never become audit substitute.

Not required for MVP.

---

# 129. UI SECURITY TEST CONTRACT

P0 tests must verify:

- tenant A resource never appears in tenant B page;
- unauthorized actions absent and server-blocked;
- direct URL cannot bypass authorization;
- Inertia props do not leak unauthorized data;
- attachment URL protected;
- approval action authority enforced;
- tenant switch clears tenant-sensitive state;
- public QR exposes allowlisted fields only;
- stale critical action rejected;
- support mode cannot bypass support grant.

---

# 130. UI ACCESSIBILITY TEST CONTRACT

Required:

- keyboard navigation;
- focus visibility;
- modal focus trap;
- label association;
- accessible validation;
- contrast;
- semantic tables;
- responsive zoom;
- touch target;
- screen-reader accessible critical controls.

---

# 131. RESPONSIVE TEST MATRIX

Minimum:

```text
360px mobile
390px mobile
768px tablet
1024px small desktop
1280px desktop
1440px desktop
```

Test portrait mobile as priority for inventory workflows.

---

# 132. CRITICAL E2E FLOWS

Must eventually receive E2E coverage:

```text
Login
Tenant selection/switch
Create asset
Edit permitted asset
Upload/download evidence
Generate QR
Mutation
Maintenance
Create inventory session
Scan/verify asset
Resolve discrepancy
Finalize inventory
Submit approval
Approve/reject/revision
Generate report
Finalize report
Download report
Import assets
Export data
Role/authority enforcement
```

---

# 133. IMPLEMENTATION PRIORITY

### UI Foundation

1. Design tokens
2. App shell
3. Navigation
4. Base form controls
5. Table/list
6. Status system
7. Error/empty/loading states
8. Modal/drawer
9. Responsive primitives

### Core Domain

10. Dashboard
11. Asset list
12. Asset detail
13. Asset form
14. Documents
15. QR
16. Mutation
17. Maintenance

### Governance

18. Inventory
19. Approval
20. Reporting
21. Audit

### Administration

22. Master data
23. User/RBAC
24. Officials/authority
25. Tenant settings

---

# 134. DESIGN REVIEW GATE

A page cannot be considered production-ready merely because it visually matches mockup.

Review must include:

```text
Visual consistency
Responsive behavior
Permission behavior
Authority behavior
Loading
Empty
Error
Validation
Conflict
Accessibility
Keyboard
Tenant isolation
Workflow correctness
Audit implications
```

---

# 135. NO FIGMA-ONLY CONTRACT

Figma/mockups may help implementation.

But canonical behavior comes from:

```text
PRD
Workflow
RBAC/Authority
ERD/Data Dictionary
UI/UX Specification
```

Visual design cannot override domain/security behavior.

---

# 136. ACCEPTANCE CRITERIA

UI/UX architecture is acceptable when:

- menu hierarchy is predictable;
- tenant context is visible;
- critical workflows expose state;
- permission and authority remain distinct;
- asset pages work desktop/mobile;
- inventory is mobile-first;
- approval is evidence-first;
- reporting respects immutability;
- empty/loading/error/conflict states exist;
- accessibility baseline exists;
- critical actions use deliberate confirmation;
- frontend does not become security boundary;
- UI terminology matches domain terminology.

---

# 137. SOURCE OF TRUTH

Conflict resolution:

**Applicable Regulation**
**→ RTM**
**→ Master Blueprint**
**→ PRD**
**→ Workflow**
**→ RBAC & Authority**
**→ ERD**
**→ Data Dictionary**
**→ UI/UX Specification**
**→ Implementation**

UI must adapt to domain contract.

Domain contract must not be weakened merely to simplify UI.

---

# 138. FINAL UX CONTRACT

> **DESATARA harus terasa sederhana tanpa menyembunyikan konsekuensi administratif dari tindakan pengguna.**

> **Pengguna harus selalu memahami tenant, status, kewenangan, bukti, dan tindakan berikutnya.**

> **Critical workflow tidak boleh direduksi menjadi dropdown status.**

> **Mobile merupakan first-class experience untuk inventarisasi lapangan.**

> **Desktop merupakan primary workspace untuk administrasi, reporting, dan governance kompleks.**

> **UI membantu pengguna mengambil tindakan yang benar; server menentukan apakah tindakan tersebut sah.**

---

# 139. STATUS

**UI/UX SPECIFICATION + INFORMATION ARCHITECTURE DESATARA v1.0 — LOCKED**

Dokumen ini menjadi baseline UI/UX DESATARA sebelum implementasi frontend.
