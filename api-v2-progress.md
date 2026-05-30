# API v2 — Progreso SDD

**Fecha**: 2026-05-29
**Proyecto**: lvPassport (`/home/fvasquez/Code/SAIL/api/lvPassport`)
**Objetivo**: Construir API v2 con SDD, manteniendo v1 (TDD) intacta.

---

## Estado actual

**Implementación completa y verificada (`/sdd-verify`). Mergeado a `main` junto con la feature de roles/permisos de V1.**

| Fase | Tasks | Estado |
|------|-------|--------|
| 1 — Infraestructura | 5/5 | ✓ |
| 2 — Auth | 3/3 | ✓ |
| 3 — Resources (Schemas/Requests/Authorizers) | 10/10 | ✓ |
| 4 — Comandos | 2/2 | ✓ |
| 5 — Tests V2 | 19/19 | ✓ |
| 6 — Verificación (`/sdd-verify`) | — | ✓ |

**Suite de tests: 323/323 pasando** (V1 + V2 combinados tras el merge).

---

## Artefactos Engram

| Artefacto | ID | Topic Key | En el repo |
|-----------|----|-----------|-----------|
| Init context | #102 | `sdd-init/lvPassport` | — |
| Explore (mapa de V1) | #103 | `sdd/api-v2/explore` | — |
| Proposal | #104 | `sdd/api-v2/proposal` | — |
| Design | #105 | `sdd/api-v2/design` | — |
| Spec (escenarios) | #106 | `sdd/api-v2/spec` | **[`specs/api-v2/`](specs/api-v2/README.md)** ✓ |
| Tasks (39 tasks) | #107 | `sdd/api-v2/tasks` | — |

> **Nota:** sin Engram en sesión, la spec se **reconstruyó en el repo** como escenarios
> Given-When-Then en `specs/api-v2/` (un archivo por test), derivada de los tests de `tests/Feature/V2/` y de la
> implementación. Es ahora la copia versionada y autoritativa dentro del proyecto.

---

## Decisiones de diseño tomadas

| # | Pregunta | Decisión |
|---|----------|----------|
| 1 | Token sin permisos en V2 Login | Emitir scope mínimo `read` (no wildcard `*`) |
| 2 | AuthorPolicy reads | `tokenCan()` solo — sin `hasPermissionTo()` para lecturas |
| 3 | `generate:permissions` en producción | Proceder — idempotente, solo agrega permisos |

---

## Archivos creados/modificados

### Infraestructura
- `app/JsonApi/V2/Server.php` — V2 server, baseUri `/api/v2`
- `config/jsonapi.php` — registra servidor v2
- `routes/api.php` — rutas V2 (HTTP + JSON:API)
- `app/Providers/AppServiceProvider.php` — scope `read` + AuthorPolicy registrada

### Auth
- `app/Http/Controllers/Api/V2/LoginController.php` — login con scopes explícitos
- `app/Policies/AuthorPolicy.php` — reads con `tokenCan('read')`, writes deniegan

### Resources V2
- `app/JsonApi/V2/Articles/` — ArticleSchema, ArticleRequest, ArticleAuthorizer
- `app/JsonApi/V2/Categories/` — CategorySchema (con filtros/sort/paginación), CategoryRequest, CategoryAuthorizer
- `app/JsonApi/V2/Authors/` — AuthorSchema, AuthorRequest, AuthorAuthorizer (Gate::inspect)

### Modelo
- `app/Models/Category.php` — agregados `scopeName`, `scopeSlug`, `scopeSearch`

### Comandos
- `app/Console/Commands/GeneratePermissions.php` — agrega `articles:update-authors`, `articles:update-categories`, permiso `read`

### Tests V2 (`tests/Feature/V2/`)
- `InfrastructureTest.php`
- `Auth/` — LoginTest, LogoutTest, AuthenticatedUserTest, AuthorPolicyTest
- `Articles/` — CreateArticlesTest, UpdateArticlesTest, DeleteArticlesTest, ListArticlesTest, FilterArticlesTest
- `Categories/` — CreateCategoriesTest, UpdateCategoriesTest, DeleteCategoriesTest, ListCategoriesTest, FilterCategoriesTest, SortCategoriesTest, PaginateCategoriesTest
- `Authors/` — ListAuthorsTest
- `Commands/GeneratePermissionsTest.php` (actualizado)

---

## Diferencias V2 vs V1

| Aspecto | V1 | V2 |
|---------|----|----|
| Login scope | Scopes derivados de los permisos del usuario (`getAllPermissions`) | Scopes explícitos del request, fallback `read` |
| AuthorAuthorizer | Raw `tokenCan()` | `Gate::inspect()` → AuthorPolicy |
| Categories filtros | No tiene | name, slug, search |
| Categories sorts | No tiene | name, slug, createdAt, updatedAt |
| Categories paginación | No tiene | PagePagination |
| Permisos relaciones | Solo CRUD base | + `articles:update-authors`, `articles:update-categories` |

---

## Próximo paso

Verificación `/sdd-verify api-v2` **completada** (commit `9df2078`) y mergeada a `main`. La implementación cumple los escenarios de la spec (#106) y la suite (323/323) pasa.

Pendientes opcionales:
- Documentar V2 en el `README.md` (hoy describe solo V1).
- Decidir si V1 se deja como legacy o se deprecia a favor de V2.

---

## Comandos útiles

```bash
# Correr solo tests V2
vendor/bin/sail artisan test --compact --filter=V2

# Correr suite completa
vendor/bin/sail artisan test --compact

# Ver rutas V2
vendor/bin/sail artisan route:list --path=api/v2

# Generar permisos V2
vendor/bin/sail artisan generate:permissions
```
