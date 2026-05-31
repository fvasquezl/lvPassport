---
name: v2-resource
description: "Scaffold a new JSON:API resource in API V2 (lvPassport) following the comments pattern: Schema + Request + Authorizer (+ Policy for 3-layer auth), register in V2 Server.php, add routes, declare scopes, and write Pest feature tests. Use when adding a resource under /api/v2."
argument-hint: "Resource name (singular), e.g. 'tag' or 'reaction'"
metadata:
  author: lvPassport
user-invocable: true
disable-model-invocation: false
---

# Add a V2 JSON:API resource

Scaffold a new resource under `/api/v2`, mirroring the existing V2 resources (`Comments` is the
canonical 3-layer example; `Categories` is the scope+permission-without-ownership example). Honor the
project constitution: JSON:API triad, layered authorization in Authorizers/Policies (never
controllers), test-first with Pest, versioning without breaking V1, Pint + Sail discipline.

**All commands run through Sail** (`vendor/bin/sail ...`). Prefer `vendor/bin/sail artisan make:*` to
create files so they match framework stubs, then edit.

## Step 0 — Clarify the shape (ask in prose if not given)

Confirm before scaffolding:
1. **Resource name** (singular model) and JSON:API **type** (kebab-plural, e.g. `tags`).
2. **Attributes** (name + type) and which are read-only (`createdAt`/`updatedAt`).
3. **Relationships** (belongsTo / hasMany; JSON:API type alias — recall `authors` aliases the `user`
   relation via `->type('authors')`).
4. **Read visibility**: public (like articles/categories) or scope-gated (like authors)?
5. **Write authorization**: scope + permission **+ ownership** (like articles/comments), or scope +
   permission only (like categories)? Is there an owner column (`user_id`)?
6. **Querying**: filters / sorts / pagination needed?

If this is real feature work, suggest doing it spec-first via `/speckit-specify` → `/speckit-plan` →
`/speckit-tasks` first; this skill is the implementation mechanics.

## Step 1 — Model, migration, factory

- `vendor/bin/sail artisan make:model {Name} -mf` (model + migration + factory).
- Migration: columns per Step 0; FKs (`user_id` is a `char(36)` UUID FK → `users`; other FKs per the
  related model). Add owner column only if the resource has ownership.
- Model: `$guarded = []` (match siblings), `belongsTo`/`hasMany` relations, and a `$jsonApiTypes` map
  if any relation needs a type alias. Add the inverse relation on the related model (e.g.
  `Article::comments()`).
- Factory: realistic faker values; related ids via factories.

## Step 2 — JSON:API triad under `app/JsonApi/V2/{Name}/`

Mirror `app/JsonApi/V2/Comments/`:
- **`{Name}Schema.php`** — `ID`, attributes (`createdAt`/`updatedAt` read-only via `->readOnly()` or
  sortable as siblings do), `BelongsTo`/`HasMany` relationships (apply `->type('authors')` where the
  relation is the user), filters/sorts/pagination if requested, and reference the Authorizer.
- **`{Name}Request.php`** — validation rules; required relationships via `JsonApiRule::toOne()` /
  `toMany()`; slug `required|unique|` + format if applicable; text fields with sane `max:`.
- **`{Name}Authorizer.php`** — public reads return `true` (or gate on scope for authors-style);
  `store`/`update`/`destroy` delegate scope+permission via `Gate::inspect(...)` to a Policy. For
  ownership: `store` ownership is a **payload** check inside the authorizer (the declared owner id ==
  authenticated user, returns `Response::deny()` on mismatch); `update`/`destroy` ownership lives in
  the Policy (`$model->user->is($user)`). Relationship writes return `false` unless explicitly needed.

If the resource has writes, create **`app/Policies/{Name}Policy.php`**: `viewAny`/`view` public;
`create` = `tokenCan('{type}:store') && hasPermissionTo('{type}:store')`; `update`/`delete` add
`&& $model->user->is($user)` for ownership. Register the policy in `AppServiceProvider` if the project
doesn't auto-discover it (check siblings).

## Step 3 — Register & route

- Add `{Name}Schema::class` to `app/JsonApi/V2/Server.php` → `allSchemas()`.
- Add the resource (and any relationship routes) to the **v2 block** in `routes/api.php`
  (`JsonApiRoute::server('v2')`). Read-only relationships use the read-only relationship route only.

## Step 4 — Scopes & permissions

- Declare new scopes in `AppServiceProvider`'s `Passport::tokensCan([...])`:
  `{type}:store|update|delete` (+ `{type}:update-{relation}` for relationship writes, following the
  `{type}:{verb}-{relationship}` convention).
- Permissions are created on the `api` guard; tests create them in `beforeEach`. For runtime, wire
  them into the `generate:permissions` command pattern if appropriate.

## Step 5 — Tests (delegate to the `pest-tester` agent or follow its conventions)

Create `tests/Feature/V2/{Name}/` with one file per behavior area, mirroring Comments/Categories:
List (public/guest/no-scope reads + full JSON:API structure), Create (201 happy; 401 guest; 403 no
scope; 403 no permission; 403 wrong owner if applicable; 422 validation + `source.pointer`), Update,
Delete, and Filter/Sort/Paginate if querying was requested. Cover the **super-admin** path
(update/delete any → 2xx; remember `store` ownership is NOT bypassed). Use the `tests/Pest.php`
helpers (`userWithPermission`, `userWithRole`, `jsonData`) and `Passport::actingAs($user, [scopes])`.

## Step 6 — Verify & close

```bash
vendor/bin/sail artisan test --compact --filter={Name}    # the new resource, green
vendor/bin/sail artisan test --compact                    # full suite, no regression to V1/V2
vendor/bin/sail bin pint --dirty --format agent           # style
```

Report files created, test names, and **actual** run counts. Do not weaken or delete existing tests.
V1 must remain untouched (constitution IV).
