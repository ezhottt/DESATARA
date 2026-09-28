# AGENTS.md — DESATARA

## 1. Purpose

This file defines repository-level operating rules for AI coding agents and human contributors implementing **DESATARA — Platform Pengelolaan Aset Desa**.

It does not replace product documentation. It tells implementers how to use the locked contracts safely.

## 2. Mandatory reading order

Before changing behavior, read the relevant contracts in this order:

1. `docs/DOCUMENT-CONTROL.md`
2. `docs/REGULATORY-MATRIX.md` when regulatory behavior is involved
3. `docs/ARCHITECTURE.md`
4. `docs/PRD.md`
5. `docs/WORKFLOWS.md` and `docs/RBAC.md`
6. `docs/ERD.md` and `docs/DATA-DICTIONARY.md`
7. `docs/SECURITY.md`
8. `docs/UI-UX.md` and `docs/DESIGN.md` for UI work
9. `docs/API.md` for API/integration work
10. `docs/DEPLOYMENT.md` for runtime/deployment work
11. `docs/TESTING.md`
12. `docs/IMPLEMENTATION_PLAN.md`

Do not assume this file overrides those contracts.

## 3. Source-of-truth rule

Implementation must conform to the locked documentation. Do not modify a contract merely to make an implementation easier.

If two contracts appear contradictory, STOP at that boundary. Identify the conflict and resolve the authoritative document according to `DOCUMENT-CONTROL.md` before continuing.

Do not invent regulatory requirements, authority, approval chains, retention periods, RPO/RTO values, external integration behavior, or official interoperability that the contracts do not establish.

## 4. Architecture invariants

- Laravel owns authoritative business behavior.
- Inertia.js is the default first-party application bridge.
- Vue owns interaction/presentation, not authorization or domain truth.
- PostgreSQL is the primary relational database.
- DESATARA is a modular monolith.
- First-party web does not default to REST API transport.
- `/api/v1` exists only for justified integration/external/mobile/M2M boundaries.
- Multi-tenancy is structural, not a UI filter.
- Client-provided `tenant_id` never establishes ownership.
- Platform Admin is not automatically a tenant operator.
- Role, permission, official position, and regulatory authority are distinct.
- Finalized/historical records are not silently rewritten.

## 5. Tenant and authorization rules

Every tenant-owned operation must resolve tenant context server-side and enforce server-side authorization.

For critical tenant-owned relationships, preserve tenant-aware referential integrity defined by ERD/Data Dictionary.

Cross-tenant data exposure through controllers, policies, jobs, cache, exports, notifications, Inertia props, files, QR lookup, reports, or APIs is a P0 defect.

Never rely on hidden buttons, Vue conditions, route obscurity, or request payloads as authorization.

## 6. Authority and workflow rules

Permission does not imply regulatory authority.

Where authority is required, validate the appropriate assignment and validity period according to RBAC/Workflow contracts. Recheck authority at execution/finalization where required.

Preserve requester/submitter/approver identity and separation-of-duties rules.

Do not implement generic status setters for regulated workflows. Use explicit domain transitions/actions with validation, transaction boundaries, audit evidence, and concurrency protection.

## 7. Historical integrity

Prefer append-only events/history for lifecycle facts defined as historical truth.

Do not overwrite finalized reports, approval actions, inventory finalization, authority snapshots, or linked evidence.

Corrections must use the documented correction/revision/reconciliation mechanism.

## 8. Persistence rules

Do not create migrations before the relevant ERD/Data Dictionary contract is understood.

Use PostgreSQL-relevant types and constraints.

Critical tenant relationships should use composite tenant-aware keys/FKs where specified.

Money uses exact numeric/decimal semantics, never floating point.

Use stable UUID/public identifiers for major tenant-owned resources where required.

## 9. Security rules

- Private evidence remains private by default.
- Never expose storage paths, secrets, stack traces, or cross-tenant resource existence.
- Validate input server-side.
- Protect against IDOR and mass-assignment bypass.
- Use rate limits on abuse-sensitive boundaries.
- Preserve audit/security events.
- Never weaken security checks to make tests pass.
- No production secret in source control, frontend bundles, fixtures, logs, or examples.

## 10. UI implementation rules

Follow `UI-UX.md` for information architecture and interaction behavior.

Follow `DESIGN.md` for visual tokens/components.

Do not create a card-heavy dashboard, decorative complexity, or hidden critical consequences.

Responsive behavior and WCAG 2.2 AA target are part of acceptance, not optional polish.

Unauthorized actions should be absent where appropriate and always blocked server-side.

## 11. API rules

Do not create API endpoints as a convenience duplicate of Inertia actions.

When an API boundary is justified, follow `API.md`; use versioned routes, public identifiers, tenant-safe authorization, controlled filtering, stable error semantics, concurrency/idempotency where required, and auditable mutations.

OpenAPI must describe implemented behavior, not fictional future endpoints.

## 12. Testing rules

Every behavior change requires focused regression coverage at the correct layer.

P0 areas include tenant isolation, authorization/authority, direct endpoint bypass, private documents, workflow transitions, SoD, finalized history, inventory reconciliation, reporting finalization, queue/cache tenant context, and API/Inertia leakage.

PostgreSQL-specific behavior must be tested against PostgreSQL-relevant infrastructure; SQLite-only success is insufficient evidence.

Do not delete or weaken tests merely to obtain green CI.

## 13. Batch discipline

Follow `IMPLEMENTATION_PLAN.md` dependency order.

Each batch follows:

**Implement → Test → Review → Verify → Lock → Next**

Do not pull future-domain implementation into an earlier batch merely because it is convenient.

B00 is runtime/repository foundation only. It must not introduce asset CRUD/domain tables.

## 14. Change discipline

Keep changes scoped and reviewable.

Before claiming completion:
- inspect the diff;
- run relevant tests;
- run formatter/linter/static/build gates required by the current batch;
- verify no unintended documentation or migration drift;
- report commands and evidence truthfully.

Do not claim PASS without current reproducible evidence.

## 15. STOP conditions

STOP and report before proceeding when:
- contracts materially contradict;
- regulatory interpretation is missing for a claimed regulatory rule;
- tenant isolation cannot be proven;
- authority can be bypassed;
- migration risks irreversible data loss without an approved strategy;
- finalized/history integrity would be weakened;
- a P0 regression remains;
- implementation requires silently changing a LOCKED contract.

## 16. Repository hygiene

Do not commit generated secrets, local environment files, database dumps, private evidence, vendor/node dependency directories, or production artifacts unless explicitly intended by repository policy.

Keep product documentation under `docs/`, machine-readable API contract under `openapi/`, and repository agent instructions at root.

**DESATARA AGENT OPERATING CONTRACT — LOCKED**
