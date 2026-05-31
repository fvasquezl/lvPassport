# Contract — Comments API (V2)

Base: `/api/v2` · Media type: `application/vnd.api+json` · Resource type: `comments`

## Endpoints

| Method | Path | Action | Auth | Authorization |
|--------|------|--------|------|---------------|
| GET | `/api/v2/comments` | index | public | — |
| GET | `/api/v2/comments/{id}` | show | public | — |
| POST | `/api/v2/comments` | store | `auth:api` | scope+perm `comments:store` + author == actor |
| PATCH | `/api/v2/comments/{id}` | update | `auth:api` | scope+perm `comments:update` + owns |
| DELETE | `/api/v2/comments/{id}` | destroy | `auth:api` | scope+perm `comments:delete` + owns |
| GET | `/api/v2/articles/{article}/comments` | related (read-only) | public | — |
| GET | `/api/v2/articles/{article}/relationships/comments` | relationship (read-only) | public | — |

## Resource object

```json
{
  "data": {
    "type": "comments",
    "id": "1",
    "attributes": {
      "body": "Great article!",
      "createdAt": "2026-05-29T12:00:00.000000Z",
      "updatedAt": "2026-05-29T12:00:00.000000Z"
    },
    "relationships": {
      "author":  { "data": { "type": "authors", "id": "<uuid>" } },
      "article": { "data": { "type": "articles", "id": "<slug>" } }
    },
    "links": { "self": "/api/v2/comments/1" }
  }
}
```

## Create request

```json
POST /api/v2/comments
{
  "data": {
    "type": "comments",
    "attributes": { "body": "Great article!" },
    "relationships": {
      "author":  { "data": { "type": "authors",  "id": "<authenticated-user-uuid>" } },
      "article": { "data": { "type": "articles", "id": "<article-slug>" } }
    }
  }
}
```

## Responses

| Situation | Status |
|-----------|--------|
| Read OK | 200 |
| Create OK | 201 |
| Update OK | 200 |
| Delete OK | 204 |
| No token on a write | 401 |
| Missing scope / permission / not owner | 403 |
| Author != authenticated user (store) | 403 |
| Empty/blank `body` | 422 — pointer `/data/attributes/body` |
| Missing `author` or `article` relationship (store) | 422 — pointer `/data/relationships/{rel}` |

## Error pointers (JSON:API)

- Validation errors return `errors[].source.pointer` matching the offending member, consistent with
  existing V2 resources (e.g. `/data/attributes/body`, `/data/relationships/article`).
