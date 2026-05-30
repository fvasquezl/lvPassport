<!--
Sync Impact Report
- Version change: (template) → 1.0.0
- Bump rationale: Initial ratification of the project constitution (first concrete version).
- Modified principles: all five placeholders defined for the first time:
  - [PRINCIPLE_1] → I. JSON:API Compliance
  - [PRINCIPLE_2] → II. Layered Authorization (NON-NEGOTIABLE)
  - [PRINCIPLE_3] → III. Test-First with Pest (NON-NEGOTIABLE)
  - [PRINCIPLE_4] → IV. API Versioning Without Breakage
  - [PRINCIPLE_5] → V. Convention & Tooling Discipline
- Added sections: Technology Constraints & Security; Development Workflow & Quality Gates
- Removed sections: none
- Templates requiring updates:
  - ✅ .specify/templates/plan-template.md (Constitution Check aligns — generic gates)
  - ✅ .specify/templates/spec-template.md (no mandatory section conflicts)
  - ✅ .specify/templates/tasks-template.md (testing/versioning task types compatible)
- Follow-up TODOs: none
-->

# lvPassport Constitution

## Core Principles

### I. JSON:API Compliance
Every API resource MUST conform to the [JSON:API specification](https://jsonapi.org/) and be
expressed through the Laravel JSON:API triad: a **Schema** (fields, filters, sorts, pagination,
relationships), a **Request** (payload validation), and an **Authorizer** (per-action access).
Schemas MUST be registered in the corresponding version `Server.php`. Custom controllers are used
only when `JsonApiController` cannot express the behavior. Rationale: a single, predictable
resource shape keeps clients, tests, and docs aligned and makes new resources mechanical to add.

### II. Layered Authorization (NON-NEGOTIABLE)
Protected actions MUST be authorized by the intersection (logical AND) of up to three layers:
1. **Scope** (Passport token) — `tokenCan('...')`
2. **Permission** (Spatie, user-bound) — `hasPermissionTo('...')`
3. **Ownership** — the resource belongs to the acting user, where applicable.

Authorization MUST live in Authorizers/Policies, never in controllers. Tokens MUST NOT be issued
with wildcard `*` scopes for first-party login flows; scopes MUST be explicit or a documented
minimal default. Rationale: defense in depth — a compromised token, an over-permissioned user, or
a cross-tenant access attempt each fail independently.

### III. Test-First with Pest (NON-NEGOTIABLE)
Every behavioral change MUST be covered by a Pest test, and the affected tests MUST pass before the
change is considered done. The test suite is the executable source of truth for behavior. New
features SHOULD be specified first (SDD) and verified by tests; bug fixes MUST add a regression
test. Tests MUST NOT be deleted without explicit approval. Rationale: behavior that isn't tested is
behavior that silently breaks.

### IV. API Versioning Without Breakage
Published API versions are immutable contracts. A change MUST NOT alter the observable behavior of
an existing version in a backward-incompatible way. New or changed behavior goes into a new version
(e.g., `/api/v2`) that coexists with the prior one; both keep independent routes, schemas, and
tests. Removing a version is a deliberate, announced action, not a refactor. Rationale: consumers
pin to a version and must never be surprised.

### V. Convention & Tooling Discipline
New code MUST follow the conventions of sibling files (structure, naming, types). PHP MUST pass
Laravel Pint before changes are finalized. All PHP, Artisan, Composer, and Node commands MUST run
through Laravel Sail. Dependencies and base directory structure MUST NOT change without approval.
Documentation files are created only when explicitly requested. Rationale: consistency and a clean
toolchain keep the codebase reviewable and reproducible.

## Technology Constraints & Security

- Stack: PHP 8.5, Laravel 13, Laravel JSON:API, Laravel Passport (OAuth2), Spatie Permission,
  Pest 4 / PHPUnit 12, MySQL, Tailwind 4 — all orchestrated via Laravel Sail.
- The `api` guard MUST be consistent across Passport and Spatie; permissions are created on the
  `api` guard and the Spatie cache cleared (`PermissionRegistrar::forgetCachedPermissions()`).
- The `User` model uses UUIDs and `HasApiTokens`. Permissions follow the `{type}:{ability}` naming
  (e.g. `articles:store`); relationship abilities follow `{type}:{verb}-{relationship}`.
- The `super-admin` role bypasses authorization via `Gate::before` and is immutable (cannot be
  edited or deleted through the API).

## Development Workflow & Quality Gates

- New resource checklist: create Schema + Request + Authorizer under `app/JsonApi/V{n}/{Resource}/`,
  register in that version's `Server.php`, add routes in `routes/api.php`, implement authorization
  in the Authorizer, and add Pest feature tests.
- Before finalizing any PHP change: run the relevant Pest tests (`vendor/bin/sail artisan test`)
  and `vendor/bin/sail bin pint`.
- SDD features keep their artifacts in `specs/<feature>/` versioned in git (spec → plan → tasks).
- Commits/PRs MUST verify compliance with these principles; deviations MUST be justified.

## Governance

This constitution supersedes ad-hoc practices for this repository. Amendments MUST be made by
editing this file, MUST follow semantic versioning (MAJOR: principle removed/redefined; MINOR:
principle or section added/materially expanded; PATCH: clarifications), and MUST update the Sync
Impact Report and the dependent `.specify/templates/*`. Compliance is reviewed at code-review time;
any complexity or deviation from a principle MUST be explicitly justified in the change description.
Runtime development guidance for AI agents lives in `CLAUDE.md`.

**Version**: 1.0.0 | **Ratified**: 2026-05-29 | **Last Amended**: 2026-05-29
