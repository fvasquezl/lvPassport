# Phase 1 — Data Model: API v2 Base

V2 introduces **no new tables** — it re-exposes existing entities under `/api/v2` with hardened
authorization. The entities below are described as the V2 JSON:API surface presents them.

## Entity: Article (type `articles`)

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| `id` / `slug` | string | unique, slug format | route key is the slug |
| `title` | string | required, non-empty | |
| `content` | text | required, non-empty | |
| `slug` | string | required, unique, slug format (no underscores, no leading/trailing dash) | |
| `category_id` | integer (FK → categories.id) | set via `categories` relationship | |
| `user_id` | char(36) UUID (FK → users.id) | set via `authors` relationship; == owner | |
| `created_at` / `updated_at` | timestamp | auto | exposed as `createdAt` / `updatedAt` |

- **Attributes**: `title`, `slug`, `content`, `createdAt` (ro), `updatedAt` (ro)
- **Relationships**: `authors` → BelongsTo User (`->type('authors')`); `categories` → BelongsTo Category
- **Filters**: `title`, `content`, `year`, `month`, `search`, `categories`, `authors`

## Entity: Category (type `categories`)

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| `id` / `slug` | string | unique, slug format | route key is the slug |
| `name` | string | required, non-empty | |
| `slug` | string | required, unique, slug format | |
| `created_at` / `updated_at` | timestamp | auto | exposed as `createdAt` / `updatedAt` |

- **Attributes**: `name`, `slug`, `createdAt` (ro), `updatedAt` (ro)
- **Relationships**: hasMany `articles`
- **Filters**: `name`, `slug`, `search` · **Sorts**: `name`, `slug`, `createdAt`, `updatedAt` · **Pagination**: page-based
- Model query scopes: `scopeName`, `scopeSlug`, `scopeSearch`

## Entity: Author / User (type `authors`)

| Field | Type | Notes |
|-------|------|-------|
| `id` | char(36) UUID | route key |
| `name` | string | |
| `email` | string | |

- Read-only projection of `User`. No writable attributes via the JSON:API resource.
- Relationship: `roles` (write requires `authors:update-roles`).

## Entity: Role (type `roles`, Spatie)

| Field | Type | Notes |
|-------|------|-------|
| `id` | integer | |
| `name` | string | |
| `guard_name` | string | defaults to `api` when created via API |

- Full CRUD for administrators. `super-admin` is **immutable** (no update/delete).

## Entity: Permission (type `permissions`, Spatie)

- Read-only (`index`, `show`). No write routes exposed.

## Relationships

```
Category (1) ──hasMany──▶ (N) Article ◀──belongsTo── (1) User/Author
                                                          │
User/Author (N) ──belongsToMany──▶ (N) Role ──belongsToMany──▶ (N) Permission
```

## Authorization matrix

| Resource / action | Auth required | Layers |
|-------------------|---------------|--------|
| `articles` index/show/filter | none (public) | — |
| `articles` store | yes | scope + permission `articles:store` + author == actor |
| `articles` update | yes | scope + permission `articles:update` + owns |
| `articles` update authors/categories rel | yes | scope + permission `articles:update-authors` / `articles:update-categories` + owns |
| `articles` delete | yes | scope + permission `articles:delete` + owns |
| `categories` index/show/filter/sort/paginate | none (public) | — |
| `categories` store/update/delete | yes | scope + permission `categories:*` (no ownership) |
| `authors` index/show | yes | scope `read` (via `AuthorPolicy`, no Spatie permission) |
| `authors` writes | — | always denied via API |
| `authors.roles` write | yes | scope + permission `authors:update-roles` + super-admin self-removal guard |
| `roles` CRUD | yes | scope + permission `roles:*` (shared `RolePolicy`); `super-admin` immutable |
| `permissions` index/show | yes | scope + permission `permissions:*` (shared `PermissionPolicy`) |
| any (super-admin) | yes | bypass via `Gate::before` (except self-removal guard) |

## Auth (non-resource) surface

- `POST /api/v2/login` — issues a token with explicit scopes (fallback `['read']`, never `['*']`).
- `POST /api/v2/logout` — revokes the current token.
- `GET /api/v2/user` — returns the authenticated user; no specific scope required beyond being authenticated.
