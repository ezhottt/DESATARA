# DOCUMENT CONTROL & CONTRACT INDEX — DESATARA

**Document Version:** 1.0  
**Status:** CONTROLLED BASELINE
**Product:** DESATARA — Platform Pengelolaan Aset Desa  
**Purpose:** Canonical documentation governance and contract precedence

## 1. Purpose

Dokumen ini mengendalikan source of truth DESATARA sebelum dan selama implementasi. Ia tidak menggantikan kontrak domain; ia menentukan dokumen mana yang authoritative untuk concern tertentu, dependency antar-kontrak, status, dan cara menyelesaikan konflik.

## 2. Contract hierarchy

Urutan interpretasi: **Regulasi berlaku → Regulatory Traceability Matrix → Architecture → PRD → Workflow/RBAC → ERD → Data Dictionary → Security/UI-UX/Design → API/Deployment → Testing → Implementation Plan → implementation artifacts.**

Dokumen yang lebih hilir tidak boleh melemahkan invariant yang ditetapkan dokumen lebih hulu. Untuk concern spesifik, dokumen authoritative pada Contract Index tetap menjadi sumber utama selama tidak bertentangan dengan regulasi atau invariant arsitektur.

## 3. Contract index

| Contract | Version | Status | Authoritative scope |
|---|---:|---|---|
| `REGULATORY-MATRIX.md` | 1.0 | CONTROLLED BASELINE | regulatory traceability, provision-to-rule mapping |
| `ARCHITECTURE.md` | 3.0 | CONTROLLED BASELINE | system architecture, tenancy, domain boundaries, architectural invariants |
| `PRD.md` | 1.0 | CONTROLLED BASELINE | product functional/non-functional requirements and scope |
| `WORKFLOWS.md` | 1.0 | CONTROLLED BASELINE | state transitions, workflow behavior, historical transition rules |
| `RBAC.md` | 1.0 | CONTROLLED BASELINE | role, permission, official position, authority, SoD/access contract |
| `ERD.md` | 1.1 | CONTROLLED BASELINE | logical data model and relationships |
| `DATA-DICTIONARY.md` | 1.1 | CONTROLLED BASELINE | physical data contract, field/type/constraint semantics |
| `UI-UX.md` | 1.0 | CONTROLLED BASELINE | information architecture, interaction and accessibility contract |
| `DESIGN.md` | 1.0 | CONTROLLED BASELINE | visual language, semantic design tokens and component appearance |
| `SECURITY.md` | 1.0 | CONTROLLED BASELINE | security controls, threat boundaries and production security gates |
| `TESTING.md` | 1.0 | CONTROLLED BASELINE | verification, acceptance and release evidence |
| `IMPLEMENTATION_PLAN.md` | 1.1 | CONTROLLED BASELINE | dependency-ordered delivery batches and implementation gates |
| `API.md` | 1.0 | CONTROLLED BASELINE | API boundary, versioning and protocol-level contract |
| `DEPLOYMENT.md` | 1.0 | CONTROLLED BASELINE | runtime topology and deployment contract |

## 4. Conflict resolution

1. Regulasi berlaku mengalahkan dokumentasi internal.  
2. Verified regulatory interpretation di RTM mengalahkan asumsi implementasi.  
3. Architecture mengalahkan convenience implementation.  
4. PRD menentukan *what*; Workflow/RBAC menentukan transition dan actor/authority; ERD/Data Dictionary menentukan persistence.  
5. Security dapat memperketat implementation control tetapi tidak menciptakan kewenangan regulatif baru.  
6. Testing memverifikasi kontrak; test tidak boleh menjadi sumber requirement baru.  
7. Implementation Plan menentukan urutan kerja, bukan mengubah requirement.  
8. Konflik nyata harus dihentikan, didokumentasikan, lalu diselesaikan pada kontrak authoritative sebelum kode mengikuti salah satu sisi.

## 5. Change control

Perubahan branding/non-substantive tidak memerlukan version bump bila semantic contract tidak berubah. Perubahan substantif terhadap requirement, invariant, authority, workflow, data contract, security boundary, atau acceptance gate harus melalui review dokumen authoritative dan sinkronisasi downstream sebelum implementasi.

Status `CONTROLLED BASELINE` berarti kontrak telah disepakati sebagai baseline implementasi, tetapi dapat berubah melalui reviewed PR ketika didukung perubahan regulasi, verified contradiction, security finding, atau implementation evidence. Perubahan wajib memperbarui kontrak authoritative dan downstream impact secara eksplisit; baseline tidak boleh dilemahkan diam-diam.

## 6. Implementation contract

Kode, migration, policy, service, UI, API, job, report, import/export, test, dan deployment configuration harus dapat ditelusuri ke kontrak yang relevan. Repository-level operating rules untuk coding agent berada di root `AGENTS.md`; file tersebut mengarahkan implementasi ke contract index ini dan tidak mengalahkan dokumen authoritative. Ketika implementation menemukan contradiction, pekerjaan berhenti pada boundary tersebut sampai kontrak diperbaiki secara eksplisit.

## 7. Future controlled artifacts

Artifact berikut dibuat ketika lifecycle membutuhkannya: `BACKUP-RESTORE.md`, `CHANGELOG.md`, OpenAPI machine-readable contract, runbook operasi, dan release evidence. Ketiadaannya sebelum fase yang ditentukan tidak boleh ditafsirkan sebagai izin mengabaikan requirement upstream.

**DOCUMENT CONTROL & CONTRACT INDEX DESATARA v1.0 — CONTROLLED BASELINE**


## 8. Current implementation checkpoint (2026-10-05)

- B00-B25 are integrated on `main` through baseline commit `b0f1f23`.
- B26 Excel Import & Legacy Data Migration plus product UX closeout is proposed in PR #3 from `feat/b26-ui-closeout`.
- B26 does not alter the controlled regulatory, tenancy, authority, workflow, or historical-integrity contracts.
- Current local verification evidence on the PR branch: PHPUnit **135 passed / 506 assertions**, Pint **185 files PASS**, PHPStan **0 errors**, Vite production build **PASS**, and `git diff --check` **PASS**.
- GitHub Actions workflow is configured as four parallel gates (`test`, `pint`, `phpstan`, `frontend`). Run #8 was triggered but jobs were not started because GitHub reported an account-level billing lock; this is external evidence blockage, not a green CI result.
- B17 remains fail-closed: `PRODUCTION READY = NO` until mandatory external evidence is current and machine-verifiable.


### B26.1 UI hardening checkpoint

B26.1 is a non-contract-changing product-surface hardening on PR #3. It corrects Vue template surface selection, human-readable localization, and tracked UI source encoding without changing regulatory, tenancy, workflow, or historical-integrity invariants. Fresh local gate: **137 tests / 514 assertions**, Pint **186 files**, PHPStan **0 errors**, Vite **PASS**.


### B26.2 final asset-label identity checkpoint - 2026-10-05

The earlier B26.2 label implementation and subsequent NUP terminology correction are superseded by the final inventory-identity contract documented in `IMPLEMENTATION_PLAN.md #95`.

Final contract highlights:
- inventory code = tenant village code / master item code / acquisition year / NUP;
- NUP generated with existing `numbering_sequences`, scoped by tenant + acquisition year;
- stable public UUID QR verification;
- single, selected bulk, and filtered bulk preview/print;
- Small/Medium/Large operational presets;
- DB uniqueness and identity immutability migration;
- current + legacy import compatibility.

Production deployment remains outside this checkpoint.


### B26.2 Label Aset Desa final checkpoint - 2026-10-05

Status: implementation and local verification GREEN. Identity composition is tenant village code + classification master code + acquisition year + NUP. NUP allocation is race-safe and scoped by tenant/classification/year. Label preview supports single, selected bulk, and current search-filter bulk printing with Small/Medium/Large operational presets and copy count. Public UUID verification is allowlisted. Legacy import compatibility is preserved. Production deployment and PR merge remain outside this checkpoint.
