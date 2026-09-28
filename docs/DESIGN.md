# DESIGN SYSTEM & VISUAL LANGUAGE — DESATARA

**Document Version:** 1.0  
**Status:** LOCKED  
**Product:** DESATARA — Platform Pengelolaan Aset Desa  
**Parent:** UI/UX Specification v1.0  
**Scope:** Visual system, tokens, component appearance, responsive visual consistency

## 1. Purpose

Dokumen ini mengatur bahasa visual DESATARA. `UI-UX.md` tetap authoritative untuk information architecture, task flow, interaction behavior, state behavior, dan accessibility behavior. Dokumen ini tidak mengubah domain workflow atau authorization.

## 2. Design principles

DESATARA harus terasa:

- modern;
- formal tetapi tidak kaku;
- bersih;
- tenang;
- data-first;
- mudah dipindai;
- konsisten;
- cocok untuk aplikasi pemerintahan;
- tidak menyerupai dashboard dekoratif atau landing page marketing.

Visual hierarchy harus membantu keputusan dan pekerjaan administratif, bukan memamerkan komponen.

## 3. Token-first rule

Implementasi tidak boleh menyebarkan arbitrary visual values tanpa alasan. Warna, typography, spacing, radius, border, shadow, state, dan layout primitives didefinisikan sebagai semantic design tokens melalui Tailwind/theme layer.

Token name harus berbasis fungsi seperti `surface`, `text-muted`, `border`, `primary`, `success`, `warning`, `danger`, bukan nama layar tertentu.

Exact token values dikunci saat B00/B15 melalui visual review tanpa mengubah semantic contract dokumen ini.

## 4. Color system

Arah brand: **blue/navy sebagai primary government-tech identity**, neutral surfaces untuk data density, dan semantic colors untuk status.

Gunakan semantic roles:
- primary / primary-emphasis;
- surface / surface-subtle / surface-raised;
- text / text-muted / text-disabled;
- border / border-strong;
- success;
- warning;
- danger;
- info;
- focus.

Warna semantic tidak boleh menjadi satu-satunya pembeda status. Selalu kombinasikan dengan text/icon/label yang dapat dipahami.

Dark mode **bukan MVP requirement**, tetapi token architecture tidak boleh membuat dark mode future membutuhkan rewrite komponen.

## 5. Typography

Gunakan sans-serif modern yang memiliki keterbacaan tinggi untuk Bahasa Indonesia, angka, tabel, dan form.

Hierarchy minimal:
- page title;
- section title;
- component title;
- body;
- supporting/meta text;
- label;
- table text;
- numeric/data emphasis.

Jangan memakai ukuran/weight ekstrem untuk data administratif. Angka penting harus mudah dibandingkan dan tidak terpotong.

## 6. Spacing and density

Gunakan spacing scale konsisten. Desktop mendukung information density yang efisien; mobile mempertahankan tap target dan readability.

Hindari whitespace berlebihan yang membuat tabel/form administratif membutuhkan scroll tanpa manfaat.

Dense mode tidak boleh mengorbankan accessibility.

## 7. Shape, borders, elevation

Gunakan radius moderat dan konsisten. Hindari tampilan terlalu playful/pill-heavy.

Border digunakan untuk struktur dan pemisahan data. Shadow/elevation dipakai hemat untuk overlay, modal, dropdown, atau raised surface yang benar-benar membutuhkan depth.

Jangan membuat setiap section sebagai floating card.

## 8. Layout primitives

Komponen layout utama:
- application shell;
- sidebar/drawer;
- topbar;
- page header;
- content section;
- toolbar/filter bar;
- data table/record list;
- detail section;
- tabs;
- stepper;
- form grid;
- modal/dialog;
- drawer;
- status/feedback region.

Desktop sidebar + topbar dan mobile drawer mengikuti `UI-UX.md`.

## 9. Dashboard visual contract

Dashboard bersifat decision-support.

Prioritaskan:
1. tindakan yang memerlukan perhatian;
2. ringkasan aset;
3. status/kondisi;
4. workflow;
5. inventarisasi;
6. pelaporan;
7. aktivitas relevan.

Tidak boleh menjadi card festival. Gunakan cards hanya ketika grouping atau emphasis memang membantu comprehension.

## 10. Tables and record lists

Desktop data-heavy views menggunakan table yang readable dengan alignment konsisten, header jelas, sorting/filter state terlihat, dan action tidak mendominasi.

Mobile menggunakan record-list adaptation bila tabel penuh tidak usable.

Status memakai semantic badge yang readable; jangan menggunakan warna saja.

Bulk action harus memperlihatkan selection count dan consequence.

## 11. Forms

Label harus persistent dan jelas. Placeholder bukan pengganti label.

Kelompokkan field berdasarkan task/domain. Required/optional state konsisten. Validation message berada dekat field dan memiliki summary/focus strategy bila diperlukan.

Destructive atau irreversible action tidak boleh terlihat seperti primary safe action.

## 12. Buttons and actions

Hierarchy:
- primary: satu tindakan utama dalam context;
- secondary: tindakan pendukung;
- tertiary/text: low-emphasis;
- danger: destructive/high-risk.

Jangan menampilkan banyak primary buttons bersaing dalam satu region.

Icon-only button wajib memiliki accessible name dan tooltip bila makna tidak langsung jelas.

## 13. Feedback states

Setiap data surface yang asynchronous harus memiliki state yang relevan:
- loading;
- empty;
- error;
- success/confirmation;
- stale/conflict;
- unauthorized/forbidden bila route dicapai;
- offline/degraded bila kelak relevan.

Skeleton digunakan hanya bila membantu perceived continuity; jangan menyamarkan error atau authorization failure sebagai loading.

## 14. Modals and irreversible actions

Modal dipakai untuk focused task, bukan menggantikan halaman kompleks.

Critical confirmation menjelaskan object, action, dan consequence. Untuk workflow regulatif, UI harus membedakan request, approval, execution, external/formal decision, dan finalization.

Escape/focus trap/restore focus mengikuti accessibility contract.

## 15. Navigation states

Active navigation harus jelas tanpa mengandalkan warna saja.

Active tenant selalu terlihat. Tenant switch harus menunjukkan perubahan context dan tidak mempertahankan sensitive stale state dari tenant sebelumnya.

Breadcrumb digunakan ketika hierarchy membantu orientation, bukan sebagai dekorasi wajib.

## 16. Icons

Gunakan satu icon family konsisten. Icon mendukung label, bukan menggantikan terminology domain penting.

Hindari campuran icon styles, emoji sebagai production navigation icon, dan decorative illustration pada data-critical surfaces.

## 17. Responsive system

Baseline review widths mengikuti Testing contract: 360, 390, 768, 1024, dan 1440 px.

Responsive adaptation mempertahankan:
- action discoverability;
- tenant context;
- status meaning;
- table/list comprehension;
- form order;
- validation;
- approval consequence;
- accessibility.

Mobile priority mencakup QR/inventory/photo/approval workflows.

## 18. Accessibility visual rules

Target WCAG 2.2 AA.

Pastikan contrast, visible focus, non-color state cues, scalable text, keyboard-visible interaction, adequate touch targets, readable error states, dan reduced-motion-friendly behavior.

Animation tidak boleh menjadi syarat memahami state.

## 19. Data visualization

Chart hanya digunakan jika membantu perbandingan/trend/distribution. Jangan mengubah data yang lebih jelas sebagai angka/tabel menjadi chart dekoratif.

Chart wajib memiliki title/context, readable labels atau accessible alternative, dan tidak bergantung pada warna saja.

## 20. Documents, QR and evidence

Document/evidence UI harus menampilkan status yang relevan seperti availability, scan/storage state, supersession/finality bila applicable tanpa mengekspos private path.

QR visual tidak boleh mengungkap identifier sensitif atau menggantikan opaque token contract.

## 21. Design-to-code governance

Reusable visual primitives harus menjadi shared components ketika pola benar-benar berulang.

Jangan membuat global abstraction sebelum pattern terbukti.

Page-specific styling tidak boleh memperkenalkan visual language baru tanpa review.

UI screenshot review tidak menggantikan functional/accessibility tests.

## 22. Definition of visual consistency

Sebuah implementation dianggap konsisten bila:
- memakai semantic tokens;
- mengikuti component hierarchy;
- responsive;
- state lengkap;
- tidak melanggar UI/UX behavior;
- tidak menyembunyikan security/workflow consequence;
- memenuhi accessibility target;
- tidak menambahkan decorative complexity tanpa fungsi.

**DESIGN SYSTEM & VISUAL LANGUAGE DESATARA v1.0 — LOCKED**
