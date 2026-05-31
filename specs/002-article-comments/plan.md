# Implementation Plan: Article Comments (V2)

**Branch**: `002-article-comments` | **Date**: 2026-05-29 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/002-article-comments/spec.md`

## Summary

Add a `comments` JSON:API resource to API **V2**: a comment belongs to an article and an author
(User); an article has many comments (read-only relationship). Reads are public; writes use the
project's three-layer authorization (scope + Spatie permission + ownership). Implementation mirrors
the existing V2 `articles`/`categories` resources (Schema + Request + Authorizer) **plus a dedicated
`CommentPolicy`**: the `CommentAuthorizer` delegates scope+permission to the policy via
`Gate::inspect`, the policy also enforces ownership on `update`/`delete`, and the authorizer enforces
`store` ownership against the request payload. It adds a `Comment` model + migration + factory,
registers the schema and routes, and is verified with Pest feature tests. V1 is untouched.

## Technical Context

**Language/Version**: PHP 8.5

**Primary Dependencies**: Laravel 13, laravel-json-api/laravel, Laravel Passport (OAuth2),
Spatie Permission

**Storage**: MySQL — new `comments` table (`id` PK, `body` text, `article_id` FK→articles.id,
`user_id` FK→users.id (UUID), timestamps)

**Testing**: Pest 4 (`tests/Feature/V2/Comments/`) with `RefreshDatabase` + `MakesJsonApiRequests`

**Target Platform**: Linux server via Laravel Sail (Docker)

**Project Type**: Web service (versioned JSON:API)

**Performance Goals**: N/A (standard API expectations; no special targets)

**Constraints**: Must not change observable behavior of V1 or existing V2 resources

**Scale/Scope**: One new resource + one relationship; ~20 feature tests

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Compliance |
|-----------|-----------|
| I. JSON:API Compliance | ✅ Comment resource = Schema + Request + Authorizer, registered in V2 `Server.php` |
| II. Layered Authorization | ✅ Writes require scope + permission + ownership; reads public (consistent with V2) |
| III. Test-First with Pest | ✅ Pest feature tests written before/with implementation; suite must stay green |
| IV. Versioning Without Breakage | ✅ Added only under `/api/v2`; V1 and existing V2 resources untouched |
| V. Convention & Tooling Discipline | ✅ Mirrors sibling V2 resources; Pint; Sail; new permissions via existing `generate:permissions` pattern |

**Result**: PASS — no violations, Complexity Tracking not required.

## Project Structure

### Documentation (this feature)

```text
specs/002-article-comments/
├── plan.md              # This file
├── spec.md              # Feature spec
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output (endpoint contracts)
└── checklists/
    └── requirements.md  # Spec quality checklist
```

### Source Code (repository root)

```text
app/
├── Models/
│   └── Comment.php                              # new model (belongsTo article, user)
├── Policies/
│   └── CommentPolicy.php                         # create: scope+permission; update/delete: scope+permission+ownership
└── JsonApi/V2/Comments/
    ├── CommentSchema.php                         # fields, relationships, authorizer ref
    ├── CommentRequest.php                        # body required; author/article relationships
    └── CommentAuthorizer.php                     # delegates to CommentPolicy via Gate::inspect; store-ownership vs payload

database/
├── migrations/
│   └── XXXX_XX_XX_create_comments_table.php      # comments table
└── factories/
    └── CommentFactory.php                        # for tests/seeders

app/JsonApi/V2/
├── Server.php                                    # register CommentSchema
└── Articles/ArticleSchema.php                    # add HasMany('comments') (read-only rel)

app/Models/Article.php                            # add comments() hasMany relation
app/Policies/ArticlePolicy.php                    # showComments (authorize article → comments read)

routes/api.php                                    # v2 comments resource + articles.comments rel

tests/Feature/V2/Comments/
├── CreateCommentsTest.php
├── ListCommentsTest.php
├── UpdateCommentsTest.php
└── DeleteCommentsTest.php
tests/Feature/V2/Articles/
└── IncludeCommentsTest.php                       # article → comments relationship reads
```

**Structure Decision**: Single Laravel application; the feature slots into the existing
`app/JsonApi/V2/` versioned structure, mirroring `articles`/`categories`. No new base folders.

## Complexity Tracking

> No constitution violations — section intentionally empty.
