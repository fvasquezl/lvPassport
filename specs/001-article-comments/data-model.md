# Phase 1 — Data Model: Article Comments (V2)

## Entity: Comment

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| `id` | integer (PK, auto-increment) | — | JSON:API id & route key (D1) |
| `body` | text | required, non-empty, max 2000 | the comment content (D6) |
| `article_id` | integer (FK → articles.id) | required | parent article |
| `user_id` | char(36) UUID (FK → users.id) | required | author of the comment |
| `created_at` | timestamp | auto | exposed as `createdAt` (read-only) |
| `updated_at` | timestamp | auto | exposed as `updatedAt` (read-only) |

### JSON:API representation (type `comments`)

- **Attributes**: `body`, `createdAt` (read-only), `updatedAt` (read-only)
- **Relationships**:
  - `author` → `BelongsTo` User, JSON:API type `authors` (D2)
  - `article` → `BelongsTo` Article, JSON:API type `articles`

## Relationships

```
Article (1) ──── hasMany ────▶ (N) Comment ◀──── belongsTo ──── (1) User (author)
              (read-only rel)
```

- `Article::comments()` — `hasMany(Comment::class)`, exposed read-only on the article resource.
- `Comment::article()` — `belongsTo(Article::class)`.
- `Comment::user()` — `belongsTo(User::class)`, exposed as `author` (type `authors`).

## Validation rules (from spec FR-008, Assumptions)

- `body`: `required`, `string`, not blank, `max:2000`.
- `author` relationship: required on create; must reference the authenticated user (ownership, D3).
- `article` relationship: required on create; must reference an existing article.

## Authorization matrix

| Action | Auth required | Layers |
|--------|---------------|--------|
| `index`, `show` | none (public) | — |
| `article → comments` (read) | none (public) | — |
| `store` | yes | scope `comments:store` + permission `comments:store` + author == actor |
| `update` | yes | scope `comments:update` + permission `comments:update` + owns comment |
| `delete` | yes | scope `comments:delete` + permission `comments:delete` + owns comment |
| any (super-admin) | yes | bypass via `Gate::before` |

## State

Comments have no lifecycle states (no draft/published/hidden) — flat, immutable except by their
author. (Assumptions: no moderation in v1.)
