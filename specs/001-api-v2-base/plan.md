# Implementation Plan: API v2 Base (versioned JSON:API with hardened auth + RBAC)

**Branch**: `001-api-v2-base` | **Date**: 2026-05-30 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/001-api-v2-base/spec.md`

> **Retrofit note**: this plan documents an already-implemented and merged feature. It is reconstructed
> from the V2 source under `app/JsonApi/V2/`, `app/Policies/`, `app/Http/Controllers/Api/V2/` and the
> Pest suite under `tests/Feature/V2/`. The chronologically-later `comments` work is tracked separately
> in [`002-article-comments`](../002-article-comments/plan.md).

## Summary

Stand up a second JSON:API server (V2) under `/api/v2` that runs **in parallel** with V1 without changing
it. V2 ships resource parity with V1 (`articles`, `categories`, `authors`, `roles`, `permissions`) plus a
hardened authentication design (explicit, least-privilege scopes — no wildcard) and the project's
three-layer authorization (Passport scope + Spatie permission + ownership). Categories additionally gain
filters, sorting and pagination. Each resource is a Schema + Request + Authorizer; V2 has its own
`Server.php`, its own `LoginController`, and its own route block. Authorization for `authors`, `roles` and
`permissions` reuses global policies shared with V1.

## Technical Context

**Language/Version**: PHP 8.5

**Primary Dependencies**: Laravel 13, laravel-json-api/laravel, Laravel Passport (OAuth2), Spatie Permission

**Storage**: MySQL — existing `articles`, `categories`, `users` (UUID), Spatie `roles`/`permissions` tables;
no new tables introduced by this feature.

**Testing**: Pest 4 (`tests/Feature/V2/`) with `RefreshDatabase` + `MakesJsonApiRequests`

**Target Platform**: Linux server via Laravel Sail (Docker)

**Project Type**: Web service (versioned JSON:API)

**Performance Goals**: N/A (standard API expectations; no special targets)

**Constraints**: MUST NOT change observable behavior of V1 (versioning without breakage)

**Scale/Scope**: One new API version, 5 resources, hardened login, RBAC parity, querying on categories;
~21 feature test files.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Compliance |
|-----------|-----------|
| I. JSON:API Compliance | ✅ Every V2 resource = Schema + Request + Authorizer, registered in V2 `Server.php`; routes via `JsonApiRoute::server('v2')` |
| II. Layered Authorization | ✅ Writes require scope + permission (+ ownership for articles); authors reads gated by scope; reads of articles/categories public |
| III. Test-First with Pest | ✅ Behavior captured by Pest feature tests under `tests/Feature/V2/`; suite stays green |
| IV. Versioning Without Breakage | ✅ Everything added under `/api/v2`; V1 server, routes and behavior untouched |
| V. Convention & Tooling Discipline | ✅ Mirrors V1 structure; Pint; Sail; permissions via `generate:permissions` |

**Result**: PASS — no violations, Complexity Tracking not required.

## Project Structure

### Documentation (this feature)

```text
specs/001-api-v2-base/
├── plan.md              # This file
├── spec.md              # Feature spec (consolidated user stories)
├── research.md          # Phase 0 output (design decisions D1–D8)
├── data-model.md        # Phase 1 output (entities + authz matrix)
├── quickstart.md        # Phase 1 output (run/verify)
├── contracts/
│   └── api-v2.md        # Endpoint contracts (auth + all resources)
└── checklists/
    └── requirements.md  # Spec quality checklist
```

### Source Code (repository root)

```text
app/JsonApi/V2/
├── Server.php                       # V2 server, baseUri /api/v2, allSchemas()
├── Articles/   { ArticleSchema, ArticleRequest, ArticleAuthorizer }
├── Categories/ { CategorySchema (filters/sorts/pagination), CategoryRequest, CategoryAuthorizer }
├── Authors/    { AuthorSchema, AuthorRequest, AuthorAuthorizer (Gate::inspect → AuthorPolicy) }
├── Roles/      { RoleSchema, RoleRequest, RoleAuthorizer }
└── Permissions/{ PermissionSchema, PermissionAuthorizer }

app/Http/Controllers/Api/V2/
└── LoginController.php               # explicit-scope token issuance (no wildcard)

app/Policies/
├── AuthorPolicy.php                  # author reads via tokenCan('read'); writes denied
├── RolePolicy.php / PermissionPolicy.php   # shared with V1 (scope + permission)

app/Models/Category.php               # scopeName / scopeSlug / scopeSearch

app/Providers/AppServiceProvider.php  # Passport::tokensCan (incl. read); Gate::before super-admin; policies
config/jsonapi.php                    # register v2 server
routes/api.php                        # v2 HTTP (login/logout/user) + JsonApiRoute::server('v2')

tests/Feature/V2/
├── InfrastructureTest.php
├── Auth/      { LoginTest, LogoutTest, AuthenticatedUserTest, AuthorPolicyTest }
├── Articles/  { CreateArticlesTest, ListArticlesTest, UpdateArticlesTest, DeleteArticlesTest, FilterArticlesTest }
├── Categories/{ CreateCategoriesTest, ListCategoriesTest, UpdateCategoriesTest, DeleteCategoriesTest,
│                FilterCategoriesTest, SortCategoriesTest, PaginateCategoriesTest }
├── Authors/   { ListAuthorsTest }
├── Roles/     { RolesCrudTest }
├── Permissions/{ IndexPermissionsTest }
└── AuthorsRoles/{ AssignRolesTest }
```

**Structure Decision**: Single Laravel application. V2 slots alongside V1 under `app/JsonApi/V{n}/`, with a
parallel route block and login controller. No new base folders; no new database tables.

## Complexity Tracking

> No constitution violations — section intentionally empty.
