# DESATARA Real Online Demo (staging only)

This branch contains the actual Laravel 13 / Inertia / Vue application. It is **not** the static `vercel-demo` showcase.

## Isolated infrastructure prepared

- Railway project: `desatara-public-demo`, environment: `demo`.
- Staged services (NOT RUNNING): `desatara-laravel-demo`, `Postgres` with `POSTGRES_DB=desatara_demo`.
- Git source: `ezhottt/DESATARA`, branch `demo/online-laravel-vercel`.
- No existing production service, DB, or `main` branch is modified.

## Required before activating staged deployment

1. Review the code and run PHPUnit, Pint, PHPStan, frontend build and vulnerability scans. CI in the account was previously blocked by GitHub billing. Do not treat missing tests as success.
2. Generate independent random `APP_KEY` and `DESATARA_DEMO_ADMIN_PASSWORD` in a secure secret manager; never store either in Git. Create an isolated demo account in the DB. Use `APP_ENV=demo`, `DESATARA_DEMO_ENABLED=true`, `APP_DEBUG=false`, `DB_CONNECTION=pgsql`, `DB_URL=${{Postgres.DATABASE_URL}}`, `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`, `MAIL_MAILER=log`, `LOG_CHANNEL=stderr`, `SESSION_SECURE_COOKIE=true` (on HTTPS).
3. Configure PHP 8.3+ / Composer / Node and `npm ci && npm run build` through Railway build settings (or a vetted multi-stage Dockerfile), and a verified web-server launch targeting Laravel `public/index.php` with port `$PORT`. A default static Vite build is not a Laravel server.
4. Only on the isolated `desatara_demo` database: run `php artisan desatara:check-public-demo`, then `php artisan migrate --force`, then `php artisan desatara:bootstrap-public-demo`; avoid repeated seeding of changed records. Review the existing local seeder's synthetic classification and register fixtures against fail-closed label requirements first.
5. Validate database-backed login, session rotation, asset list/create/edit, authorization failures, cross-tenant denial, queue-dependent flows, and frontend build before sharing a public URL. **Do not promise Delete:** this project intentionally uses lifecycle disposal and audit history instead of a raw `DELETE /assets/{id}` endpoint.
6. Add Vercel as a *separate* project with a reverse proxy **after** the Railway demo URL is live and audited. Do not point the current static showcase to this environment or reuse any production variables.
7. Upload/import, member admin, password reset, document/QR issuing and other sensitive operations are blocked by `ProtectPublicDemo` in `APP_ENV=demo`. Review all remaining write routes and public GET endpoints before granting public access. A public demonstration remains an untrusted internet-facing service.
8. Account for Railway/Vercel resource billing, storage, worker and scheduler, backups, expiration/reset strategy, monitoring and user privacy. Obtain deployment approval before activating provisioned services.

## Acceptance

The demo is **not live** until infrastructure is activated, backend credentials and build/start scripts work, security gates pass, and authenticated smoke tests have been verified. Railway staged resources are configuration drafts only. Production remains gated by `docs/PRODUCTION-READINESS.md`.
