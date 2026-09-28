# DOCUMENT CONTROL & CONTRACT INDEX — DESATARA

**Document Version:** 1.0  
**Status:** LOCKED  
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
| `REGULATORY-MATRIX.md` | 1.0 | LOCKED | regulatory traceability, provision-to-rule mapping |
| `ARCHITECTURE.md` | 3.0 | LOCKED | system architecture, tenancy, domain boundaries, architectural invariants |
| `PRD.md` | 1.0 | LOCKED | product functional/non-functional requirements and scope |
| `WORKFLOWS.md` | 1.0 | LOCKED | state transitions, workflow behavior, historical transition rules |
| `RBAC.md` | 1.0 | LOCKED | role, permission, official position, authority, SoD/access contract |
| `ERD.md` | 1.0 | LOCKED | logical data model and relationships |
| `DATA-DICTIONARY.md` | 1.0 | LOCKED | physical data contract, field/type/constraint semantics |
| `UI-UX.md` | 1.0 | LOCKED | information architecture, interaction and accessibility contract |
| `DESIGN.md` | 1.0 | LOCKED | visual language, semantic design tokens and component appearance |
| `SECURITY.md` | 1.0 | LOCKED | security controls, threat boundaries and production security gates |
| `TESTING.md` | 1.0 | LOCKED | verification, acceptance and release evidence |
| `IMPLEMENTATION_PLAN.md` | 1.0 | LOCKED | dependency-ordered delivery batches and implementation gates |
| `API.md` | 1.0 | LOCKED | API boundary, versioning and protocol-level contract |
| `DEPLOYMENT.md` | 1.0 | LOCKED | runtime topology and deployment contract |

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

Status `LOCKED` berarti baseline implementasi telah disepakati; bukan berarti dokumen kebal terhadap perubahan regulasi atau verified contradiction.

## 6. Implementation contract

Kode, migration, policy, service, UI, API, job, report, import/export, test, dan deployment configuration harus dapat ditelusuri ke kontrak yang relevan. Repository-level operating rules untuk coding agent berada di root `AGENTS.md`; file tersebut mengarahkan implementasi ke contract index ini dan tidak mengalahkan dokumen authoritative. Ketika implementation menemukan contradiction, pekerjaan berhenti pada boundary tersebut sampai kontrak diperbaiki secara eksplisit.

## 7. Future controlled artifacts

Artifact berikut dibuat ketika lifecycle membutuhkannya: `BACKUP-RESTORE.md`, `CHANGELOG.md`, OpenAPI machine-readable contract, runbook operasi, dan release evidence. Ketiadaannya sebelum fase yang ditentukan tidak boleh ditafsirkan sebagai izin mengabaikan requirement upstream.

**DOCUMENT CONTROL & CONTRACT INDEX DESATARA v1.0 — LOCKED**
