# RUNTIME & DEPLOYMENT ARCHITECTURE — DESATARA

**Document Version:** 1.0  
**Status:** LOCKED  
**Product:** DESATARA — Platform Pengelolaan Aset Desa  
**Target:** Linux VPS / Cloud

## 1. Runtime topology

```text
Internet
  ↓
TLS / Reverse Proxy
  ↓
Laravel + Inertia application
  ├── Vite-built frontend assets
  ├── PostgreSQL
  ├── Queue worker
  ├── Scheduler
  ├── Cache / session backend
  └── Private document storage
          ↓
      Backup target
```

DESATARA tetap satu **modular monolith release unit** untuk first-party web. Deployment tidak memisahkan Vue menjadi SPA server dan Laravel menjadi REST backend kecuali arsitektur dikaji ulang secara eksplisit.

## 2. Environments

Minimal terdapat local/development dan production. Staging direkomendasikan ketika release maturity membutuhkannya. Production secret, database, storage, queue, cache/session, logs, dan external credentials tidak boleh digunakan sebagai convenience development dependency.

## 3. Web boundary

Hanya web root/public assets yang boleh internet-accessible. `.env`, Git metadata, source secrets, private documents, database dump, backup archive, logs sensitif, dan internal storage tidak boleh berada pada public web path.

TLS wajib untuk production. Reverse proxy/web server meneruskan request hanya ke supported application entry point. Trusted proxy/header configuration harus eksplisit sesuai topology aktual.

## 4. Application runtime

PHP/Laravel version dikunci oleh project dependencies saat B00. Composer production install menggunakan locked dependencies. Configuration/cache optimization hanya dijalankan dengan environment yang benar. Debug mode dan verbose exception output harus nonaktif di production.

## 5. Database

PostgreSQL adalah primary database. Production migration membutuhkan backup/readiness evidence, migration review, dan rollback/forward-fix/restore strategy. SQLite-only test tidak cukup untuk PostgreSQL-specific behavior.

Database remote transport menggunakan TLS bila topology memerlukannya. Credentials least-privilege dan tidak disimpan di repository.

## 6. Queue and scheduler

Queue worker dan scheduler merupakan managed production processes. Deployment harus memastikan worker restart/reload terhadap release baru dan scheduler hanya berjalan sesuai intended singleton/concurrency behavior. Jobs membawa tenant context secara eksplisit dan tidak mengandalkan ambient request state.

## 7. Cache and session

Cache key tenant-owned menggunakan namespace tenant-aware, termasuk baseline `desatara:{tenant_id}:{domain}:{identifier}:{version}` bila sesuai. Cache/session backend production ditentukan di B00/B16 berdasarkan topology, tetapi tidak boleh melemahkan tenant isolation atau logout/revocation semantics.

## 8. Storage

Evidence/document storage bersifat private by default. Public filesystem hanya untuk artifact yang memang public. Authorized download melewati application authorization atau mekanisme signed delivery yang memenuhi security contract.

## 9. Secrets

Secret berasal dari environment/secret manager deployment, tidak dari Git. Rotation harus memungkinkan credential lama dicabut. Log, exception, health response, CI output, dan frontend bundle tidak boleh membocorkan secret.

## 10. Deploy flow

Baseline release flow: clean source → dependency install from locks → automated tests/static/build gates → production frontend build → maintenance/traffic strategy bila diperlukan → database migration → application release switch → cache/config operations → worker reload → scheduler verification → smoke test → observability check.

Deployment gagal tidak boleh dipaksa menjadi sukses dengan melemahkan migration, authorization, test, atau security gate.

## 11. Rollback and recovery

Rollback aplikasi hanya dilakukan bila kompatibel dengan schema. Untuk destructive/incompatible migration gunakan forward-fix atau restore strategy yang sudah direncanakan. Backup tidak dianggap recovery capability sampai restore drill berhasil.

Exact backup retention, RPO, dan RTO belum diklaim oleh baseline ini; nilainya harus dikunci sebelum production berdasarkan operational/regulatory requirement.

## 12. Observability

Production menyediakan structured application logs, security/audit events sesuai kontrak, queue failure visibility, scheduler health, storage/backup status, database/application health, dan deployment identifier. Health endpoint tidak boleh membocorkan secret atau sensitive topology.

## 13. Production gates

Sebelum production: tests PASS; production build PASS; migration verified; dependency/security checks PASS; private storage verified; TLS/security headers verified; queue/scheduler healthy; backup tersedia; restore drill PASS; smoke test PASS; rollback/recovery path terdokumentasi; unresolved P0 = 0.

## 14. B00 boundary

B00 hanya membangun repository/runtime foundation: Laravel, PostgreSQL connectivity, Inertia/Vue/Tailwind/Vite, tests, formatter/lint/static analysis, environment contract, filesystem, queue, dan CI. B00 tidak membuat asset CRUD atau domain tables sebelum batch domainnya.

## 15. Future operations contract

`BACKUP-RESTORE.md` dibuat sebelum production hardening untuk prosedur operasional rinci dan evidence restore. Deployment provider-specific runbook dibuat setelah hosting target nyata dipilih; baseline ini sengaja tidak mengarang provider, IP, path server, service manager, atau RPO/RTO.

**RUNTIME & DEPLOYMENT ARCHITECTURE DESATARA v1.0 — LOCKED**
