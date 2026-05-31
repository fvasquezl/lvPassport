---
description: "Task list for API v2 Base"
---

# Tasks: API v2 Base (versioned JSON:API with hardened auth + RBAC)

**Input**: Design documents from `specs/001-api-v2-base/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/api-v2.md

**Tests**: INCLUDED — Constitution Principle III (Test-First with Pest) is non-negotiable; each user story
has Pest feature tests under `tests/Feature/V2/`.

**Organization**: Grouped by user story (US1–US8 from spec.md).

**Status**: ✅ Implemented, tested, and merged to `main`. This is a **retrofit** task list reconstructed from
the V2 implementation and the Pest suite — all tasks are complete by construction. The full suite is green
(372/372 after the later comments feature; this V2 base accounts for the V2 tests below). Two implementation
notes vs. a from-scratch plan: `authors` authorization is done via `AuthorPolicy` + `Gate::inspect` (D6), and
`roles`/`permissions` reuse the **global** `RolePolicy`/`PermissionPolicy` shared with V1 (D7).

## Path Conventions

Single Laravel app. V2 resource code under `app/JsonApi/V2/`, login under `app/Http/Controllers/Api/V2/`,
policies under `app/Policies/`, tests under `tests/Feature/V2/`. Run everything via `vendor/bin/sail`.

---

## Phase 1: Setup & Foundational (Shared Infrastructure) — US1

**Goal**: A V2 JSON:API server exists under `/api/v2`, in parallel with an untouched V1.

- [x] T001 Create `app/JsonApi/V2/Server.php` (baseUri `/api/v2`, `allSchemas()`) and register it in `config/jsonapi.php`
- [x] T002 Add the v2 HTTP routes (`login`, `logout`, `user`) and the `JsonApiRoute::server('v2')` block in `routes/api.php`
- [x] T003 Declare the `read` scope (and resource scopes) in `Passport::tokensCan()` and wire `Gate::before` super-admin bypass in `app/Providers/AppServiceProvider.php`
- [x] T004 [US1] Write `tests/Feature/V2/InfrastructureTest.php` (V2 server + routes exist; V1 routes still registered)

**Checkpoint**: V2 wired, V1 intact.

---

## Phase 2: Hardened authentication — US2

- [x] T005 [US2] Create `app/Http/Controllers/Api/V2/LoginController.php` — explicit-scope token issuance, fallback `['read']`, never `['*']`
- [x] T006 [US2] Write `tests/Feature/V2/Auth/LoginTest.php`, `LogoutTest.php`, `AuthenticatedUserTest.php`

---

## Phase 3: Public content reads — US3

- [x] T007 [US3] Create `ArticleSchema`/`ArticleAuthorizer` and `CategorySchema`/`CategoryAuthorizer` with **public** index/show; register both in `Server.php`
- [x] T008 [US3] Write `tests/Feature/V2/Articles/ListArticlesTest.php` and `tests/Feature/V2/Categories/ListCategoriesTest.php`

---

## Phase 4: Article authoring (scope + permission + ownership) — US4

- [x] T009 [US4] Implement `ArticleRequest` validation (title/content required, slug unique + format, authors/categories required relationships)
- [x] T010 [US4] Implement `ArticleAuthorizer` store/update/delete + relationship updates — scope + permission + ownership; authorization precedes validation (D3/D4)
- [x] T011 [US4] Add `articles:update-authors` / `articles:update-categories` scopes+permissions (via `generate:permissions`)
- [x] T012 [US4] Write `tests/Feature/V2/Articles/CreateArticlesTest.php`, `UpdateArticlesTest.php`, `DeleteArticlesTest.php`

---

## Phase 5: Category management (scope + permission, no ownership) — US5

- [x] T013 [US5] Implement `CategoryRequest` (name/slug required, slug unique + format) and `CategoryAuthorizer` store/update/delete (scope + permission)
- [x] T014 [US5] Write `tests/Feature/V2/Categories/CreateCategoriesTest.php`, `UpdateCategoriesTest.php`, `DeleteCategoriesTest.php`

---

## Phase 6: Content discovery — filter / sort / paginate — US6

- [x] T015 [US6] Add article filters (`title`, `content`, `year`, `month`, `search`, `categories`, `authors`) to `ArticleSchema`
- [x] T016 [US6] Add category filters/sorts/pagination to `CategorySchema` and `scopeName`/`scopeSlug`/`scopeSearch` to `app/Models/Category.php`
- [x] T017 [US6] Write `tests/Feature/V2/Articles/FilterArticlesTest.php`, `Categories/FilterCategoriesTest.php`, `SortCategoriesTest.php`, `PaginateCategoriesTest.php`

---

## Phase 7: Author directory (scope-gated reads) — US7

- [x] T018 [US7] Create `AuthorSchema`/`AuthorRequest`/`AuthorAuthorizer` (Gate::inspect) and `app/Policies/AuthorPolicy.php` — reads via `tokenCan('read')`, writes denied (D5/D6)
- [x] T019 [US7] Write `tests/Feature/V2/Auth/AuthorPolicyTest.php` and `tests/Feature/V2/Authors/ListAuthorsTest.php`

---

## Phase 8: RBAC — roles, permissions, author-role assignment — US8

- [x] T020 [US8] Create `RoleSchema`/`RoleRequest`/`RoleAuthorizer` and `PermissionSchema`/`PermissionAuthorizer`; reuse global `RolePolicy`/`PermissionPolicy` (D7); `super-admin` immutable (D8)
- [x] T021 [US8] Add the `authors.roles` relationship write (scope+permission `authors:update-roles`) with super-admin self-removal guard (D8)
- [x] T022 [US8] Write `tests/Feature/V2/Roles/RolesCrudTest.php`, `Permissions/IndexPermissionsTest.php`, `AuthorsRoles/AssignRolesTest.php`

---

## Phase 9: Polish & Cross-Cutting

- [x] T023 Run `vendor/bin/sail bin pint --dirty --format agent` and fix any style issues
- [x] T024 Run the FULL suite `vendor/bin/sail artisan test --compact` — confirm no regression (V1 + V2 green) per SC-001

---

## Traceability (story → test file)

Each acceptance area is pinned by a Pest test file (the executable source of truth). This table replaces the
former one-file-per-test docs under `specs/api-v2/`.

| Story | Area | Test file |
|-------|------|-----------|
| US1 | Infrastructure / versioning | `tests/Feature/V2/InfrastructureTest.php` |
| US2 | Login | `tests/Feature/V2/Auth/LoginTest.php` |
| US2 | Logout | `tests/Feature/V2/Auth/LogoutTest.php` |
| US2 | Authenticated user | `tests/Feature/V2/Auth/AuthenticatedUserTest.php` |
| US3 | Read/list articles | `tests/Feature/V2/Articles/ListArticlesTest.php` |
| US3 | Read/list categories | `tests/Feature/V2/Categories/ListCategoriesTest.php` |
| US4 | Create articles | `tests/Feature/V2/Articles/CreateArticlesTest.php` |
| US4 | Update articles | `tests/Feature/V2/Articles/UpdateArticlesTest.php` |
| US4 | Delete articles | `tests/Feature/V2/Articles/DeleteArticlesTest.php` |
| US5 | Create categories | `tests/Feature/V2/Categories/CreateCategoriesTest.php` |
| US5 | Update categories | `tests/Feature/V2/Categories/UpdateCategoriesTest.php` |
| US5 | Delete categories | `tests/Feature/V2/Categories/DeleteCategoriesTest.php` |
| US6 | Filter articles | `tests/Feature/V2/Articles/FilterArticlesTest.php` |
| US6 | Filter categories | `tests/Feature/V2/Categories/FilterCategoriesTest.php` |
| US6 | Sort categories | `tests/Feature/V2/Categories/SortCategoriesTest.php` |
| US6 | Paginate categories | `tests/Feature/V2/Categories/PaginateCategoriesTest.php` |
| US7 | Author policy | `tests/Feature/V2/Auth/AuthorPolicyTest.php` |
| US7 | List authors | `tests/Feature/V2/Authors/ListAuthorsTest.php` |
| US8 | Roles CRUD | `tests/Feature/V2/Roles/RolesCrudTest.php` |
| US8 | Permissions (read-only) | `tests/Feature/V2/Permissions/IndexPermissionsTest.php` |
| US8 | Assign roles to authors | `tests/Feature/V2/AuthorsRoles/AssignRolesTest.php` |

## Dependencies & Execution Order

- **Setup/Foundational (US1)** → everything else.
- **US2 (auth)** is prerequisite for all writes and scope-gated reads.
- **US3 (public reads)** is the smallest consumable slice (MVP with US1+US2).
- **US4/US5** (writes) depend on US2/US3; **US6** (querying) extends US3.
- **US7/US8** depend on US2; **US8** reuses global RBAC policies (shared with V1).
- **Polish** last.
