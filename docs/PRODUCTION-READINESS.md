# DESATARA Production Readiness

**Decision baseline:** `PRODUCTION READY = NO` until all mandatory evidence below is current and machine-verifiable.

## Local validator

Run:

```text
php artisan desatara:check-readiness --production
```

The command checks safe configuration properties and the presence of opaque evidence references. It never prints their values. Configure these references through the deployment secret/environment mechanism; do not commit them:

```text
DESATARA_TLS_EVIDENCE
DESATARA_BACKUP_EVIDENCE
DESATARA_RESTORE_EVIDENCE
DESATARA_QUEUE_EVIDENCE
DESATARA_SCHEDULER_EVIDENCE
```

These references are pointers to operator-controlled records, not proof by themselves. A passing local command does not promote the release to production-ready.

## Mandatory external evidence

Record, at minimum:

- TLS and production configuration/header verification;
- PostgreSQL migration verification;
- backup availability and integrity verification;
- isolated restore smoke, including private documents and historical/report integrity;
- healthy queue worker and scheduler evidence;
- staging smoke and post-deploy smoke;
- deployment identifier, rollback/recovery path, and unresolved P0 = zero.

Provider-specific commands, hostnames, credentials, retention, RPO, and RTO remain intentionally unspecified until the target environment is approved. See [`BACKUP-RESTORE.md`](BACKUP-RESTORE.md) for the provider-neutral recovery procedure.
