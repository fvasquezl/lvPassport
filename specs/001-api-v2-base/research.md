# Phase 0 — Research: API v2 Base

No `NEEDS CLARIFICATION` remained in Technical Context. The following decisions resolve the design choices
behind V2, reconstructed from the implementation and tests. They are the authoritative record of *why* V2
behaves as it does.

## D1 — Parallel versioning (no breakage)

- **Decision**: Stand up a separate `App\JsonApi\V2\Server` (baseUri `/api/v2`) registered in
  `config/jsonapi.php`, with its own route block (`JsonApiRoute::server('v2')`) and its own
  `Api\V2\LoginController`. V1 is not touched.
- **Rationale**: Constitution principle IV (versioning without breakage). Existing V1 consumers keep working.
- **Alternatives**: Mutating V1 in place (breaks consumers); content negotiation by header (more implicit,
  harder to test/route).

## D2 — Hardened token issuance (explicit scopes, no wildcard)

- **Decision**: The V2 login mints tokens with **explicit scopes from the request**; if none are provided it
  falls back to `['read']`. It never issues the wildcard `['*']`.
- **Rationale**: Least privilege. V1 derived scopes from the user's full permission set; V2 makes the client
  ask for exactly what it will use, shrinking token blast radius.
- **Alternatives**: Wildcard tokens (V1-style convenience, rejected for security); deriving scopes from
  permissions (couples token to RBAC, less explicit).

## D3 — Three-layer authorization, applied per resource

- **Decision**: Writes are authorized by **scope (Passport) AND permission (Spatie)**, plus **ownership**
  where the resource has an owner:
  - `articles`: scope + permission + ownership (author of payload / owner of resource == actor).
  - `categories`: scope + permission (no ownership — categories have no owner).
- **Rationale**: Defense in depth; the token says *what the client may do*, the permission says *what the
  user may do*, ownership says *on which records*.
- **Alternatives**: Permission-only (ignores token scope); scope-only (ignores per-user grants).

## D4 — Authorization precedes validation

- **Decision**: When a user lacks scope/permission, the request is rejected with `403` even if the payload is
  also invalid (e.g. a missing required relationship).
- **Rationale**: Don't leak validation detail to unauthorized callers; matches the framework's authorizer →
  request ordering.

## D5 — Read visibility differs by resource

- **Decision**: `articles` and `categories` reads (index, show, filter, sort, paginate) are **public**
  (guests and no-scope tokens allowed). `authors` reads **require** a token with scope `read` (guest → 401,
  no-scope token → 403). Author **writes** are denied entirely via API.
- **Rationale**: Content is meant to be publicly consumable by the SPA; the author directory is user data and
  is gated. Author records are managed through the user/registration flow, not the JSON:API resource.
- **Alternatives**: Public authors (leaks user data); gated content reads (breaks the public SPA use case).

## D6 — Authors authorizer via Gate/Policy (not raw tokenCan)

- **Decision**: `AuthorAuthorizer` delegates to `AuthorPolicy` through `Gate::inspect`. `AuthorPolicy` reads
  use `tokenCan('read')` **only** (no Spatie permission for reads); all writes (`create`/`update`/`delete`)
  return false.
- **Rationale**: Keeps author authorization in a policy (testable in isolation, consistent with RBAC
  resources), and encodes the "reads need scope, writes forbidden" rule in one place.
- **Alternatives**: Raw `tokenCan()` inside the authorizer (V1 style) — works, but less consistent with the
  policy-based resources.

## D7 — RBAC parity reuses global policies (shared with V1)

- **Decision**: `roles`/`permissions` reuse the **global** `RolePolicy` and `PermissionPolicy` (registered in
  `AppServiceProvider`, not per version). Same authorization as V1: `tokenCan('roles:*' / 'permissions:*')`
  **and** `hasPermissionTo(...)`. No new policies or permissions are created — the V1 ones are reused.
  Roles created via API default to `guard_name` `api`. `permissions` is read-only (no store/update/destroy).
- **Rationale**: Roles and permissions are version-agnostic concerns; duplicating their policies per version
  would invite drift.

## D8 — super-admin bypass + self-removal guard

- **Decision**: The `super-admin` role bypasses all gates via `Gate::before` in `AppServiceProvider`. Two
  carve-outs: (a) the `super-admin` role itself is immutable (cannot be updated or deleted → 403); (b) a
  super-admin cannot strip the `super-admin` role from **itself** via `authors.roles` (→ 403), though it may
  demote **another** super-admin. Author-role writes require scope+permission `authors:update-roles`.
- **Rationale**: A global admin needs an escape hatch, but must not be able to lock the system out of admins
  or accidentally self-demote.
