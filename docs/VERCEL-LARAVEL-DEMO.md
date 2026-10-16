# DESATARA — Laravel 13 demo on Vercel (experimental)

**Status:** source scaffold only; unverified deployment, no live database, and no production release.

## Goal

Run the unmodified Laravel/Inertia/Vue product paths behind a Vercel PHP community function, using a wholly separate PostgreSQL demo database. Production code and production records are not migration targets.

## Source of truth

- Repository: `ezhottt/DESATARA`
- Production branch target after validation: `main`
- Compatibility branch: `feat/vercel-laravel-demo-foundation`
- Vercel project name proposal: `desatara-demo`
- Project root directory: repository root `./` (not `public`)
- Framework preset: Other
- PHP runtime: community `vercel-php@0.8.0` (PHP 8.4)
- Built frontend: `npm run build`, manifest in `public/build`
- Entry point: `api/index.php` -> Laravel `public/index.php`
- Static directory: `public`; `/` and `/index.php` must never be served as raw source

## Deployment isolation: fail closed

Set secrets ONLY in Vercel project settings, not in Git or `vercel.json`. The function returns HTTP 503 unless:

- `APP_ENV=demo`
- `DESATARA_DEMO_ENABLED=true`
- `APP_DEBUG=false`
- `APP_KEY=base64:<independent Laravel key>`
- `APP_URL=https://<actual demo domain>`
- `DESATARA_DEMO_ADMIN_PASSWORD=<unique random secret, minimum 20 characters>`
- `DB_CONNECTION=pgsql`
- `DB_URL=postgresql://...` whose database name is **desatara_demo**, or configure separate DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD fields using DB_DATABASE=desatara_demo
- `SESSION_DRIVER=database`
- `CACHE_STORE=database`
- `LOG_CHANNEL=stderr`
- `SESSION_SECURE_COOKIE=true`

The connection and database must belong only to this demo. **Never reuse production credentials, restore production data, or run migrations against a shared database.**

## Runtime constraints and unresolved blockers

1. `api/index.php` checks the env before bootstrapping, requires an isolated Neon PostgreSQL hostname (`*.aws.neon.tech`), and maps Laravel storage plus Blade/config cache to `/tmp`. Other application writes (documents, imports, downloads, generated artifacts, image/PDF processing) may still need work. Vercel's writable /tmp is ephemeral and **not** private persistent storage.
2. This branch changes `DemoSeeder` and `DemoOperationalSeeder` so online seeding works ONLY with `APP_ENV=demo`, the separate `desatara_demo` database on Neon, and a random `DESATARA_DEMO_ADMIN_PASSWORD`. The legacy local/testing password remains confined to local/test runs. Before publication, verify that demo-only placeholder asset classification/register values cannot generate fake official labels; printing and evidence-writing paths are blocked by the public demo middleware.
3. No on-demand `php artisan migrate --force` in a request path or implicit build against unknown DB. Migrations must be run deliberately on verified isolated database credentials, with tests and a safe rollback/restore plan.
4. Queue workers and a persistent scheduler cannot run as long-running background processes in Vercel Functions. Limit live demo actions to synchronous tested paths or integrate managed workers/cron with a reviewed architecture. Do **not** assume setting queue=sync is safe for every domain workflow.
5. The `ProtectPublicDemo` middleware allows only login/logout, tenant switch, asset creation and asset update as writes, applies a basic per-IP mutation rate limit, and blocks imports, admin mutations, uploads, sensitive downloads and public QR pages. A full public-demo security review still must verify rate limits, tenant/RBAC/authority checks, file upload controls, and public-GET exposure before the URL is shared. Do not expose a fixed admin password. Delete-as-CRUD is not an existing asset route: corrections/disposal use workflows, not direct record deletion.
6. A PHP runtime is a community project, not a Vercel-native PHP platform guarantee. Verify version support, Composer installation, Vite assets, function packaging and cold-start behavior against real build logs.
7. Verify source exposure resistance: GET `/index.php` must NOT reveal PHP; GET `/.env`, `/composer.json`, `/vendor/autoload.php`, `/storage/logs/laravel.log` must NOT reveal file contents. Recheck after each routing change.

## First deployment gate

1. Complete tests and security checks in a suitable local/CI environment with PHP 8.4 + PostgreSQL. At minimum PHP lint, PHPUnit, Pint, PHPStan, `npm ci && npm run build`, and dependency scanning should pass, with any limitations documented.
2. In Vercel, connect GitHub to a project `desatara-demo` (workspace `ezhottt`). The connected account currently returned **403 Forbidden** from the Vercel create-project API, so a project may require dashboard creation or scope permissions. Do not automatically retry an identical failed API request.
3. Publish a **preview** from the compatibility branch only when env isolation checks are present. Until demo DB and app key exist, HTTP 503 is expected.
4. Provision independent Neon PostgreSQL project/database `desatara_demo` (already created in Neon UI); supply the Neon URL and independent demo admin password through secure environment configuration. In a trusted PHP runtime with a deliberately verified Neon-only connection, execute `php artisan desatara:check-online-demo`, then `php artisan migrate --force`, then `php artisan desatara:seed-online-demo`. Do not run these commands inside a request handler, nor on an unknown database. The secret must never be sent through chat or committed to Git.
5. Smoke-test auth, session, tenant switch, asset create/update, RBAC cross-tenant denial, reports, CSRF, asset manifest, HTTPS proxy, and document access.
6. Only after a passing preview and code review, merge to `main`; configure Vercel production tracking as `main`. Pushes to `main` then update demo automatically. Do not merge if the app is only partially functional and the demo contract has not been agreed.

## Limitations of this change

This commit adds Vercel entrypoint/routing and a fail-closed configuration check. It does **not** prove Laravel app boot, live login, CRUD, PostgreSQL networking, PHP extensions, static build delivery, or full production suitability.
