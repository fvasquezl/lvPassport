# Contract — API v2 Base

Base: `/api/v2` · Media type: `application/vnd.api+json` (JSON:API resources) · Auth: Bearer token (Passport)

## Auth endpoints (HTTP, not JSON:API)

| Method | Path | Name | Auth | Notes |
|--------|------|------|------|-------|
| POST | `/api/v2/login` | `api.v2.login` | public | body `{ email, password, scopes? }` → `{ token, user }`; token never contains `*`; no `scopes` → `['read']` |
| POST | `/api/v2/logout` | `api.v2.logout` | `auth:api` | revokes current token → `204` |
| GET | `/api/v2/user` | `api.v2.user` | `auth:api` | returns authenticated user |

### Auth responses

| Situation | Status |
|-----------|--------|
| Login OK | 200 |
| Invalid credentials | 401 |
| Missing `email` / `password` | 422 |
| Logout OK | 204 |
| Guest on logout / user | 401 |

## Resource endpoints

| Method | Path | Action | Auth | Authorization |
|--------|------|--------|------|---------------|
| GET | `/api/v2/articles` | index | public | — (supports `filter[...]`) |
| GET | `/api/v2/articles/{slug}` | show | public | — |
| POST | `/api/v2/articles` | store | `auth:api` | scope+perm `articles:store` + author == actor |
| PATCH | `/api/v2/articles/{slug}` | update | `auth:api` | scope+perm `articles:update` + owns |
| PATCH | `/api/v2/articles/{slug}/relationships/authors` | updateRel | `auth:api` | scope+perm `articles:update-authors` + owns |
| PATCH | `/api/v2/articles/{slug}/relationships/categories` | updateRel | `auth:api` | scope+perm `articles:update-categories` + owns |
| DELETE | `/api/v2/articles/{slug}` | destroy | `auth:api` | scope+perm `articles:delete` + owns |
| GET | `/api/v2/categories` | index | public | — (supports `filter`, `sort`, `page`) |
| GET | `/api/v2/categories/{slug}` | show | public | — |
| POST | `/api/v2/categories` | store | `auth:api` | scope+perm `categories:store` |
| PATCH | `/api/v2/categories/{slug}` | update | `auth:api` | scope+perm `categories:update` |
| DELETE | `/api/v2/categories/{slug}` | destroy | `auth:api` | scope+perm `categories:delete` |
| GET | `/api/v2/authors` | index | `auth:api` | scope `read` |
| GET | `/api/v2/authors/{id}` | show | `auth:api` | scope `read` |
| PATCH | `/api/v2/authors/{id}/relationships/roles` | updateRel | `auth:api` | scope+perm `authors:update-roles` + self-removal guard |
| GET | `/api/v2/roles` | index | `auth:api` | scope+perm `roles:index` |
| POST | `/api/v2/roles` | store | `auth:api` | scope+perm `roles:store` |
| PATCH | `/api/v2/roles/{id}` | update | `auth:api` | scope+perm `roles:update`; `super-admin` immutable |
| DELETE | `/api/v2/roles/{id}` | destroy | `auth:api` | scope+perm `roles:delete`; `super-admin` immutable |
| GET | `/api/v2/permissions` | index | `auth:api` | scope+perm `permissions:index` |
| GET | `/api/v2/permissions/{id}` | show | `auth:api` | scope+perm `permissions:show` |

> `permissions` exposes **no** write routes (`store`/`update`/`destroy` do not exist).

## Querying (articles & categories)

- **Filters** (`?filter[k]=v`): articles → `title`, `content`, `year`, `month`, `search` (one or many terms,
  OR per term), `categories` (id or `id1,id2`), `authors` (name or `n1,n2`). categories → `name`, `slug`,
  `search`.
- **Sort** (`?sort=`): categories → `name`, `-name`, `slug`, `-slug`, `createdAt`, `updatedAt`.
- **Pagination** (`?page[size]=&page[number]=`): categories → page-based, with `links.first/last/prev/next`.

## Responses (resources)

| Situation | Status |
|-----------|--------|
| Read OK | 200 |
| Create OK | 201 |
| Update OK | 200 |
| Delete OK | 204 |
| No token on a write | 401 |
| Missing scope / permission / not owner | 403 |
| Immutable `super-admin` update/delete | 403 |
| super-admin self-removal of `super-admin` role | 403 |
| Malformed JSON:API / unknown attribute / unknown filter or sort | 400 |
| Required attribute empty, duplicate/invalid slug | 422 — pointer `/data/attributes/{field}` |
| Required relationship missing / wrong type | 422 — pointer `/data/relationships/{rel}` |

## Error pointers (JSON:API)

Validation errors return `errors[].source.pointer` matching the offending member (e.g.
`/data/attributes/slug`, `/data/relationships/authors`), consistent across all V2 resources.
