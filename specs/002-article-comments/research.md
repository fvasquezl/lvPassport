# Phase 0 — Research: Article Comments (V2)

No `NEEDS CLARIFICATION` remained in Technical Context. The following decisions resolve the design
choices implied by the spec, grounded in the existing V2 conventions.

## D1 — Comment identifier / route key

- **Decision**: `id` auto-increment integer, used as the JSON:API id and route key (no slug).
- **Rationale**: Comments have no natural human-readable slug (unlike articles/categories). Articles
  use `slug` because it's meaningful; comments don't need one.
- **Alternatives**: UUID (overkill, comments aren't referenced externally); slug (no natural source).

## D2 — Author relationship type

- **Decision**: `author` relationship is a `BelongsTo` to `User`, exposed with JSON:API type
  `authors` (`->type('authors')`), mirroring how `articles` exposes its `authors` relationship.
- **Rationale**: Consistency — the API already represents users as `authors`.

## D3 — Authorization mechanism & ownership enforcement point

- **Decision (as implemented)**: Authorize through a dedicated **`CommentPolicy`** that the
  `CommentAuthorizer` delegates to via `Gate::inspect`, splitting responsibilities by action:
  - `index`/`show` and relationship reads → public (the authorizer returns `true`;
    `CommentPolicy::viewAny`/`view` also return `true`).
  - `store` → the authorizer returns `401` if unauthenticated, then calls
    `Gate::inspect('create', Comment::class)` → `CommentPolicy::create` (scope `comments:store` +
    permission `comments:store`). **`store` ownership is enforced in the authorizer** against the
    request payload (`data.relationships.author.data.id` MUST equal the authenticated user), because
    at create time there is no model yet — only the payload.
  - `update`/`delete` → the authorizer calls `Gate::inspect('update'|'delete', $comment)` →
    `CommentPolicy::update`/`delete` (scope + permission + ownership via `$comment->user->is($user)`).
  - relationship writes (`updateRelationship`/`attach`/`detach`) → denied (`false`).
- **Rationale**: Keeping scope+permission in a policy makes them unit-testable in isolation and
  consistent with the V2 RBAC resources that already use `Gate`/policies; the *payload-dependent*
  `store` ownership stays in the authorizer, where the incoming request is available. All
  authorization remains out of controllers (constitution II).
- **Alternative considered**: enforcing all three layers directly inside `CommentAuthorizer`
  (mirroring `V2\Articles\ArticleAuthorizer::store`, which compares
  `data.relationships.authors.data.id`). **Not chosen** — the policy split above was preferred. Both
  approaches satisfy the constitution.

## D4 — Read visibility

- **Decision**: `index`/`show` of comments and the `article → comments` relationship reads are
  **public** (no auth), consistent with V2 articles/categories reads.
- **Rationale**: Spec FR-002/FR-003; matches V2 read model.

## D5 — Permissions & scopes

- **Decision**: Introduce `comments:index`, `comments:show`, `comments:store`, `comments:update`,
  `comments:delete`. These are generated automatically by the existing `generate:permissions`
  command (it iterates the V1 server's schema types × abilities) **only if** comments also exists in
  a server it scans. Since `generate:permissions` scans `JsonApi::server('v1')`, the V2-only
  `comments` type will NOT be auto-generated.
- **Action**: Tests create the needed permissions in `beforeEach` (as existing V2 tests do). For
  runtime, register the comment scopes in `Passport::tokensCan` and document that the permissions
  must be created (seeder/command). Keeps parity with how V2 resources already work in tests.
- **Rationale**: Avoids changing `generate:permissions` semantics (V1-scoped) under principle IV.

## D6 — body validation

- **Decision**: `body` is `required|string` and must not be empty; max length 2000 (assumption from
  spec). No HTML sanitization beyond what the app already does for article content (out of scope).
- **Rationale**: Spec FR-008 + Assumptions.

## D7 — article relationship writability

- **Decision**: On `store`, the `article` relationship is required (a comment must belong to an
  article). The `author` relationship is required and must be the acting user.
- **Rationale**: A comment with no article is invalid (edge case in spec).

## D8 — Cascade on article delete

- **Decision**: Out of scope for this feature (per Assumptions). The migration MAY add
  `onDelete('cascade')` on the `article_id` FK as a low-risk DB safeguard, but no API behavior is
  specified for it and no test covers it.
- **Rationale**: Spec explicitly defers cascade behavior.
