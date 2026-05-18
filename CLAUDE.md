# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**lvPassport** is a Laravel 13 REST API implementing the [JSON:API specification](https://jsonapi.org/) with OAuth 2.0 authentication (Laravel Passport) and role-based access control (Spatie Permission). The domain is a simple articles/categories content system.

## Commands

```bash
# First-time setup
composer setup          # install deps, copy .env, generate key, migrate, npm build

# Development (runs server + queue + logs + vite in parallel)
composer dev

# Testing
composer test           # clears config cache, then runs artisan test
php artisan test        # run all tests
php artisan test --filter "guest users cannot create"  # run a single test

# Code style
./vendor/bin/pint       # fix code style (Laravel Pint)
./vendor/bin/pint --test  # check without fixing

# Migrations
php artisan migrate
php artisan migrate:fresh --seed

# Laravel Sail (Docker alternative)
sail up -d
sail artisan migrate
sail composer test
```

## Architecture

### JSON:API Layer (`app/JsonApi/V1/`)

All API resources live under `app/JsonApi/V1/` and are registered in `Server.php`. Each resource has three files:

- **Schema** — defines fields, filters, pagination, and relationships exposed by the API
- **Request** — validates incoming JSON:API payloads
- **Authorizer** — implements `LaravelJsonApi\Contracts\Auth\Authorizer`; controls per-action access

The server base URI is `/api/v1`. Routes are registered in `routes/api.php` using `JsonApiRoute::server('v1')`. The `JsonApiController` is used by default — custom controllers are only needed for non-standard logic.

### Authentication & Authorization

- **Auth**: Laravel Passport issues OAuth 2.0 tokens. The `api` guard uses Passport. Protect routes with `auth:api` middleware.
- **Permissions**: Spatie Permission manages named permissions on the `api` guard (e.g., `articles:store`). `ArticleAuthorizer` is the integration point — check permissions there, not in controllers.
- **User model** uses UUIDs (`HasUuids`) and `HasApiTokens` (Passport).

### Models & Relationships

```
Category  ──hasMany──▶  Article  ◀──belongsTo──  User
```

Article has `title`, `slug`, `content`, `category_id` (int), `user_id` (UUID).

The JSON:API schema uses `authors` as the type alias for the `user` relationship (`BelongsTo::make('authors', 'user')->type('authors')`).

### Testing

Tests use [Pest](https://pestphp.com/) with `RefreshDatabase` and `MakesJsonApiRequests` traits applied globally to `tests/Feature/`.

**Test helpers** (defined in `tests/Pest.php`):
- `jsonData(Model $model)` — converts an Eloquent model to a JSON:API `data` payload
- `userWithPermission(string $permission)` — creates a user and grants them a named permission
- `getModelAttributes()` / `getModelRelationships()` — used internally by `jsonData()`

Permissions must be created before use in `beforeEach` (the permission cache needs explicit clearing via `PermissionRegistrar::forgetCachedPermissions()`).

### Adding a New Resource

1. Create Schema, Request, and Authorizer in `app/JsonApi/V1/{ResourceName}/`
2. Register the Schema in `app/JsonApi/V1/Server.php` → `allSchemas()`
3. Add the route in `routes/api.php` within the `JsonApiRoute::server('v1')` block
4. Implement authorization logic in the Authorizer (currently all stubbed with TODO)
