# Quickstart — API v2 Base

> Manual exercise of the V2 surface. All commands run via Sail.

## Run the feature's tests

```bash
# Whole V2 suite
vendor/bin/sail artisan test --compact --filter=V2

# By area
vendor/bin/sail artisan test --compact --filter="V2/InfrastructureTest"
vendor/bin/sail artisan test --compact --filter="V2/Auth"
vendor/bin/sail artisan test --compact --filter="V2/Articles"
vendor/bin/sail artisan test --compact --filter="V2/Categories"
vendor/bin/sail artisan test --compact --filter="V2/Authors"
vendor/bin/sail artisan test --compact --filter="V2/Roles"
vendor/bin/sail artisan test --compact --filter="V2/Permissions"
vendor/bin/sail artisan test --compact --filter="V2/AuthorsRoles"
```

## Inspect the V2 surface

```bash
vendor/bin/sail artisan route:list --path=api/v2
```

## Try it by hand

```bash
# 1. Log in asking only for the scopes you need (never a wildcard)
curl -s -X POST http://localhost/api/v2/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{ "email": "you@example.com", "password": "secret", "scopes": ["read", "articles:store"] }'

# 2. Read content publicly (no token)
curl -s http://localhost/api/v2/articles -H 'Accept: application/vnd.api+json'
curl -s 'http://localhost/api/v2/categories?sort=name&page[size]=2&page[number]=1' \
  -H 'Accept: application/vnd.api+json'

# 3. Read authors (REQUIRES a token with scope read)
curl -s http://localhost/api/v2/authors \
  -H 'Authorization: Bearer <TOKEN>' -H 'Accept: application/vnd.api+json'

# 4. Create an article (needs scope + permission articles:store, author == you)
curl -s -X POST http://localhost/api/v2/articles \
  -H 'Authorization: Bearer <TOKEN>' \
  -H 'Accept: application/vnd.api+json' -H 'Content-Type: application/vnd.api+json' \
  -d '{ "data": { "type": "articles",
        "attributes": { "title": "Hello", "slug": "hello", "content": "..." },
        "relationships": {
          "authors":    { "data": { "type": "authors",    "id": "<your-user-uuid>" } },
          "categories": { "data": { "type": "categories", "id": "<category-slug>" } } } } }'
```

## Expected

- Step 1 returns `200` with `{ token, user }`; the token contains exactly the requested scopes (or `['read']`
  if none requested) and never `*`.
- Step 2 returns `200` without a token (public reads, including filter/sort/pagination).
- Step 3 returns `200` with a `read`-scoped token; `401` without a token; `403` with a no-scope token.
- Step 4 returns `201` only with scope + permission `articles:store` and `authors` == the authenticated user;
  otherwise `401` (no token) / `403` (missing scope/permission/ownership) / `422` (invalid payload).
