# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**lvPassport** is a Laravel 13 REST API implementing the [JSON:API specification](https://jsonapi.org/) with OAuth 2.0 authentication (Laravel Passport) and role-based access control (Spatie Permission). The domain is an articles/categories content system, extended in V2 with article comments.

**Two API versions run in parallel.** V1 (`/api/v1`) exposes articles, authors, categories, roles, permissions. V2 (`/api/v2`) is the current target (the Vue client consumes V2) and adds a **comments** resource plus a hardened auth design (see below). Each version has its own `app/JsonApi/V{n}/Server.php`, its own routes, and its own `LoginController`.

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

### JSON:API Layer (`app/JsonApi/V1/` and `app/JsonApi/V2/`)

API resources live under `app/JsonApi/V{n}/` and are registered in that version's `Server.php` (`allSchemas()`). Each resource has three files:

- **Schema** — defines fields, filters, pagination, and relationships exposed by the API
- **Request** — validates incoming JSON:API payloads
- **Authorizer** — implements `LaravelJsonApi\Contracts\Auth\Authorizer`; controls per-action access

Routes are registered in `routes/api.php` using `JsonApiRoute::server('v1')` / `server('v2')` under base URIs `/api/v1` and `/api/v2`. The `JsonApiController` is used by default — custom controllers are only needed for non-standard logic.

**V2-specific**: adds the **comments** resource (`app/JsonApi/V2/Comments/`) and the `articles.comments` relationship. Comment reads are public; writes (`store`/`update`/`destroy`) require auth and enforce scope + permission + ownership in `CommentAuthorizer`. The V2 feature is tracked via spec-kit at `specs/002-article-comments/` and `api-v2-progress.md`.

### Authentication & Authorization

- **Auth**: Laravel Passport issues OAuth 2.0 tokens. The `api` guard uses Passport. Protect routes with `auth:api` middleware. OAuth scopes are declared in `AppServiceProvider` via `Passport::tokensCan([...])`.
- **V2 token issuance**: the V2 `LoginController` mints tokens with **explicit scopes only** (no wildcard `*`), defaulting to `['read']`. The client requests exactly the scopes it needs at login (e.g. `articles:store`).
- **Permissions (dual check)**: authorization requires **both** a Passport scope **and** a Spatie permission. The Authorizer is the integration point — check there, not in controllers. The `super-admin` role bypasses all gates via `Gate::before()` in `AppServiceProvider`.
- **User model** uses UUIDs (`HasUuids`), `HasApiTokens` (Passport), and `HasRoles` (Spatie).

### Models & Relationships

```
Category ──hasMany──▶ Article ◀──belongsTo── User
                         │
                      hasMany
                         ▼
                      Comment ──belongsTo──▶ User
```

Article has `title`, `slug`, `content`, `category_id` (int), `user_id` (UUID), and `hasMany(Comment)`. Comment (V2) `belongsTo` Article (`article_id`, int) and User (`user_id`, UUID).

The JSON:API schemas use `authors` as the type alias for the `user` relationship (`BelongsTo::make('authors', 'user')->type('authors')`) — on both Article and Comment.

### Testing

Tests use [Pest](https://pestphp.com/) with `RefreshDatabase` and `MakesJsonApiRequests` traits applied globally to `tests/Feature/`.

**Test helpers** (defined in `tests/Pest.php`):
- `jsonData(Model $model)` — converts an Eloquent model to a JSON:API `data` payload
- `userWithPermission(string $permission)` — creates a user and grants them a named permission
- `getModelAttributes()` / `getModelRelationships()` — used internally by `jsonData()`

Permissions must be created before use in `beforeEach` (the permission cache needs explicit clearing via `PermissionRegistrar::forgetCachedPermissions()`).

### Adding a New Resource

1. Create Schema, Request, and Authorizer in `app/JsonApi/V{n}/{ResourceName}/` (target the version you're extending — new work goes in V2)
2. Register the Schema in `app/JsonApi/V{n}/Server.php` → `allSchemas()`
3. Add the route in `routes/api.php` within the matching `JsonApiRoute::server('v{n}')` block
4. Implement authorization logic in the Authorizer (public reads vs. scope+permission+ownership writes — see `CommentAuthorizer` for the V2 pattern)
5. If writes need new scopes, declare them in `AppServiceProvider`'s `Passport::tokensCan([...])`

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- laravel/framework (LARAVEL) - v13
- laravel/passport (PASSPORT) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `vendor/bin/sail npm run build`, `vendor/bin/sail npm run dev`, or `vendor/bin/sail composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `vendor/bin/sail artisan route:list`). Use `vendor/bin/sail artisan list` to discover available commands and `vendor/bin/sail artisan [command] --help` to check parameters.
- Inspect routes with `vendor/bin/sail artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `vendor/bin/sail artisan config:show app.name`, `vendor/bin/sail artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `vendor/bin/sail artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `vendor/bin/sail artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== sail rules ===

# Laravel Sail

- This project runs inside Laravel Sail's Docker containers. You MUST execute all commands through Sail.
- Start services using `vendor/bin/sail up -d` and stop them with `vendor/bin/sail stop`.
- Open the application in the browser by running `vendor/bin/sail open`.
- Always prefix PHP, Artisan, Composer, and Node commands with `vendor/bin/sail`. Examples:
    - Run Artisan Commands: `vendor/bin/sail artisan migrate`
    - Install Composer packages: `vendor/bin/sail composer install`
    - Execute Node commands: `vendor/bin/sail npm run dev`
    - Execute PHP scripts: `vendor/bin/sail php [script]`
- View all available Sail commands by running `vendor/bin/sail` without arguments.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `vendor/bin/sail artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `vendor/bin/sail artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `vendor/bin/sail artisan list` and check their parameters with `vendor/bin/sail artisan [command] --help`.
- If you're creating a generic PHP class, use `vendor/bin/sail artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `vendor/bin/sail artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `vendor/bin/sail artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `vendor/bin/sail npm run build` or ask the user to run `vendor/bin/sail npm run dev` or `vendor/bin/sail composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/sail bin pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/sail bin pint --test --format agent`, simply run `vendor/bin/sail bin pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `vendor/bin/sail artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `vendor/bin/sail artisan make:test --pest SomeFeatureTest` instead of `vendor/bin/sail artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `vendor/bin/sail artisan test --compact` or filter: `vendor/bin/sail artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>

<!-- SPECKIT START -->
Active spec-kit feature: **Article Comments (V2)** — plan at
`specs/002-article-comments/plan.md` (spec, research, data-model, contracts, quickstart in the
same directory). Read it for technologies, structure, and conventions before implementing tasks.
<!-- SPECKIT END -->
