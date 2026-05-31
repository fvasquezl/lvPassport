# Quickstart — Article Comments (V2)

> Manual exercise of the feature once implemented. All commands run via Sail.

## Run the feature's tests

```bash
vendor/bin/sail artisan test --compact --filter=V2/Comments
vendor/bin/sail artisan test --compact --filter="V2/Articles/IncludeComments"
```

## Try it by hand

```bash
# 1. Seed data + a personal access client/token (existing helper command)
vendor/bin/sail artisan app:generate-testing-data --force

# 2. Read comments of an article (public — no token)
curl -s http://localhost/api/v2/articles/{article-slug}/comments \
  -H 'Accept: application/vnd.api+json'

# 3. Create a comment (needs a token with the comments:store scope + the user's permission)
curl -s -X POST http://localhost/api/v2/comments \
  -H 'Authorization: Bearer <TOKEN>' \
  -H 'Accept: application/vnd.api+json' \
  -H 'Content-Type: application/vnd.api+json' \
  -d '{
    "data": {
      "type": "comments",
      "attributes": { "body": "Great article!" },
      "relationships": {
        "author":  { "data": { "type": "authors",  "id": "<your-user-uuid>" } },
        "article": { "data": { "type": "articles", "id": "<article-slug>" } }
      }
    }
  }'
```

## Expected

- Step 2 returns `200` with the article's comments (even without a token).
- Step 3 returns `201` when the token has `comments:store` **and** the user has the
  `comments:store` permission **and** the `author` is the authenticated user; otherwise `403`
  (or `401` without a token, `422` for an empty body).
