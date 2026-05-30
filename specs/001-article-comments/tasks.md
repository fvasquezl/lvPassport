---
description: "Task list for Article Comments (V2)"
---

# Tasks: Article Comments (V2)

**Input**: Design documents from `specs/001-article-comments/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/comments-api.md

**Tests**: INCLUDED — Constitution Principle III (Test-First with Pest) is non-negotiable for this
project, so each user story has Pest feature tests written before its implementation.

**Organization**: Grouped by user story (US1, US2, US3 from spec.md) for independent delivery.

## Path Conventions

Single Laravel app. Resource code under `app/JsonApi/V2/Comments/`, model under `app/Models/`,
tests under `tests/Feature/V2/`. Run everything via `vendor/bin/sail`.

---

## Phase 1: Setup (Shared Infrastructure)

- [ ] T001 Add comment OAuth scopes (`comments:index`, `comments:show`, `comments:store`, `comments:update`, `comments:delete`) to `Passport::tokensCan()` in `app/Providers/AppServiceProvider.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**⚠️ Must complete before ANY user story.**

- [ ] T002 Create `comments` table migration in `database/migrations/` (`id`, `body` text, `article_id` FK→articles, `user_id` FK→users UUID, timestamps) per data-model.md
- [ ] T003 [P] Create `app/Models/Comment.php` with `article()` and `user()` belongsTo relations and `$guarded = []`
- [ ] T004 [P] Create `database/factories/CommentFactory.php` (body via faker; article_id + user_id via factories)
- [ ] T005 Add `comments()` hasMany relation to `app/Models/Article.php`
- [ ] T006 [P] Create `app/JsonApi/V2/Comments/CommentRequest.php` (rules skeleton — refined in US2)
- [ ] T007 [P] Create `app/JsonApi/V2/Comments/CommentSchema.php` (ID, `body`, `createdAt`/`updatedAt` read-only; `author` BelongsTo User `->type('authors')`; `article` BelongsTo Article; reference CommentAuthorizer)
- [ ] T008 [P] Create `app/JsonApi/V2/Comments/CommentAuthorizer.php` skeleton (all actions deny writes / allow nothing yet — refined per story)
- [ ] T009 Register `CommentSchema::class` in `app/JsonApi/V2/Server.php` → `allSchemas()`
- [ ] T010 Add `comments` resource + `articles.comments` read-only relationship routes to the v2 block in `routes/api.php`

**Checkpoint**: Resource wired (routes resolve, schema registered). User stories can begin.

---

## Phase 3: User Story 1 - Read comments of an article (Priority: P1) 🎯 MVP

**Goal**: Anyone (incl. guests) can read comments and an article's comments. Public reads.

**Independent Test**: Seed an article with comments; assert guest + authenticated both get the
comment list, a single comment, and the article→comments relationship.

- [x] T011 [P] [US1] Write `tests/Feature/V2/Comments/ListCommentsTest.php` (guest & no-scope can index/show; full JSON:API structure; collection count)
- [x] T012 [P] [US1] Write `tests/Feature/V2/Articles/IncludeCommentsTest.php` (GET `/articles/{a}/comments` and `/relationships/comments`; `?include=comments`; guest allowed)
- [ ] T013 [US1] Implement public reads in `CommentAuthorizer` (`index`, `show`, `showRelated`, `showRelationship` return true)
- [ ] T014 [US1] Add read-only `HasMany::make('comments')` to `app/JsonApi/V2/Articles/ArticleSchema.php`
- [ ] T015 [US1] Run `vendor/bin/sail artisan test --filter=V2/Comments/List` and the IncludeComments test → green

**Checkpoint**: US1 independently testable and green.

---

## Phase 4: User Story 2 - Post a comment (Priority: P1)

**Goal**: An authorized user posts a comment, becoming its author. Scope + permission + ownership.

**Independent Test**: Authorized user creates a comment on an article (201); guest 401; missing
scope/permission 403; author-is-other 403; empty body 422.

- [x] T016 [P] [US2] Write `tests/Feature/V2/Comments/CreateCommentsTest.php` (201 happy; 401 guest; 403 no scope; 403 no permission; 403 author is other user; 422 empty body; 422 missing author/article relationships)
- [ ] T017 [US2] Implement `CommentRequest` rules in `app/JsonApi/V2/Comments/CommentRequest.php` (`body` required|string|max:2000; `author` & `article` required relationships via `JsonApiRule::toOne()`)
- [ ] T018 [US2] Implement `CommentAuthorizer::store` (require `tokenCan('comments:store')` + `hasPermissionTo('comments:store')` + payload `author` id == authenticated user; mirror `V2\Articles\ArticleAuthorizer::store`)
- [ ] T019 [US2] Run `vendor/bin/sail artisan test --filter=V2/Comments/Create` → green

**Checkpoint**: US1 + US2 deliver a usable read+write comments feature.

---

## Phase 5: User Story 3 - Edit or delete own comment (Priority: P2)

**Goal**: A comment's author edits/deletes it; others cannot. Scope + permission + ownership.

**Independent Test**: Author updates/deletes own comment (200/204); non-author 403; missing
scope/permission 403.

- [x] T020 [P] [US3] Write `tests/Feature/V2/Comments/UpdateCommentsTest.php` (200 owner; 401 guest; 403 no scope; 403 no permission; 403 non-owner; 422 empty body)
- [x] T021 [P] [US3] Write `tests/Feature/V2/Comments/DeleteCommentsTest.php` (204 owner; 401 guest; 403 no scope; 403 no permission; 403 non-owner)
- [ ] T022 [US3] Implement `CommentAuthorizer::update` and `destroy` (scope + permission + `comment->user_id == actor->id`)
- [ ] T023 [US3] Run `vendor/bin/sail artisan test --filter=V2/Comments/Update` and `--filter=V2/Comments/Delete` → green

**Checkpoint**: Full comment lifecycle covered.

---

## Phase 6: Polish & Cross-Cutting

- [ ] T024 Run `vendor/bin/sail bin pint --dirty --format agent` and fix any style issues
- [ ] T025 Run the FULL suite `vendor/bin/sail artisan test --compact` — confirm no regression (V1 + existing V2 still green) per SC-005
- [ ] T026 [P] Add per-test spec files under `specs/api-v2/Comments/` and update `specs/api-v2/README.md` traceability table (optional, mirrors existing docs convention)

---

## Dependencies & Execution Order

- **Setup (T001)** → **Foundational (T002-T010)** → user stories.
- **US1 (T011-T015)** is the MVP and has no dependency on US2/US3.
- **US2 (T016-T019)** depends only on Foundational (not on US1), but ships after US1 for value order.
- **US3 (T020-T023)** depends on Foundational; logically follows US2 (needs a comment to edit/delete).
- **Polish (T024-T026)** last.

### Parallel opportunities

- Foundational: T003, T004, T006, T007, T008 are `[P]` (different files).
- Within each story, the test-writing tasks (`[P]`) can be authored in parallel before implementation.

## Implementation Strategy

- **MVP = US1** (public reading of comments) — smallest shippable slice.
- Increment: add US2 (posting) → US3 (edit/delete).
- TDD per story: write the `[P]` test task(s) first (red), then implement, then run the filtered
  suite (green) before moving on.
