# DESATARA — Vercel Static Demo

**Purpose:** independent public-safe interactive UI showcase with **fictional** browser-only data. It is **not** the Laravel application, does not demonstrate production readiness, and performs no server writes.

## Deploy on Vercel

1. Connect the GitHub repository `ezhottt/DESATARA` to a **new** Vercel project (do not reconfigure any existing production project).
2. Set **Root Directory** to `vercel-demo`.
3. Select **Framework Preset: Other**; no build or install command is needed.
4. To preserve isolation from `main`, configure **Production Branch** to `demo/vercel-static-showcase` for this *separate demo Vercel project*. If Vercel does not allow selecting it at import, configure it in **Project Settings → Environments → Production → Branch Tracking** (or Git settings depending on current dashboard), before promoting a deployment.
5. Do not add environment variables, databases, API tokens, or custom backend domains.
6. Deploy. Confirm the banner 'DEMO • BUKAN DATA ASLI' and that search works without backend requests.

## Safety contract

- All values are static examples with `SIM-` register IDs, not official codes or real village data.
- No credentials, auth pages, uploads, API calls, localStorage, cookies, or database access.
- No import from Laravel `resources`, `public`, `.env`, `storage`, or application code.
- Root directory must stay `vercel-demo`, otherwise the Laravel project may be included in the build.
- No merges to `main` required; keep demo branch separate. Do not enable automatic GitHub branch promotion to production without reviewing Vercel settings.

## Smoke checks

- Open the deployed site; the dashboard displays demo disclaimer.
- Go to Register Aset and search `SIM-003`, reset; no network API call occurs.
- Click Tambah Aset; only a demo limitation dialog appears.
- View network tab: no API calls or remote scripts.
- Validate production branch / deployment source is the demo branch, not `main`.

## Local preview

Run a static server from `vercel-demo` (for example `python -m http.server 8080`), then open `http://localhost:8080`. No npm or PHP needed.
