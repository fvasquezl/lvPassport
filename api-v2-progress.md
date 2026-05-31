# API v2 — Progreso SDD

**Fecha**: 2026-05-30 (actualizado; base original 2026-05-29)
**Proyecto**: lvPassport (`/home/fvasquez/Code/SAIL/api/lvPassport`)
**Objetivo**: Construir API v2 con SDD, manteniendo v1 (TDD) intacta.

---

## Estado actual

**Implementación completa y verificada. Mergeada a `main`.** Dos hitos:

1. **V2 base** (roles/permisos/authors/categories + auth endurecida) — verificado con `/sdd-verify`.
2. **Article Comments (V2)** — recurso `comments` + relación `articles.comments`, construido
   spec-first y test-first vía spec-kit en `specs/002-article-comments/`. Mergeado en commit
   `09918cc` (`feat(v2): article comments resource (SDD + TDD)`).

| Fase | Tasks | Estado |
|------|-------|--------|
| 1 — Infraestructura | 5/5 | ✓ |
| 2 — Auth | 3/3 | ✓ |
| 3 — Resources (Schemas/Requests/Authorizers) | 10/10 | ✓ |
| 4 — Comandos | 2/2 | ✓ |
| 5 — Tests V2 (base) | 19/19 | ✓ |
| 6 — Verificación (`/sdd-verify`) | — | ✓ |
| 7 — Article Comments (spec-kit `002-article-comments`) | 25/26 (T026 docs opcional pendiente) | ✓ |

**Suite de tests: 372/372 pasando** (V1 + V2 + comments, combinados tras el merge; era 344
antes de comments).

---

## Artefactos Engram

| Artefacto | ID | Topic Key | En el repo |
|-----------|----|-----------|-----------|
| Init context | #102 | `sdd-init/lvPassport` | — |
| Explore (mapa de V1) | #103 | `sdd/api-v2/explore` | — |
| Proposal | #104 | `sdd/api-v2/proposal` | — |
| Design | #105 | `sdd/api-v2/design` | — |
| Spec (escenarios) | #106 | `sdd/api-v2/spec` | **[`specs/001-api-v2-base/`](specs/001-api-v2-base/spec.md)** ✓ |
| Tasks (39 tasks) | #107 | `sdd/api-v2/tasks` | — |

> **Nota:** sin Engram en sesión, la spec se **reconstruyó en el repo**. Originalmente como escenarios
> Given-When-Then en `specs/api-v2/` (un archivo por test); luego **migrada a la nomenclatura spec-kit**
> en [`specs/001-api-v2-base/`](specs/001-api-v2-base/spec.md) (set completo: spec/plan/tasks/research/
> data-model/contracts/checklists/quickstart), consolidando los 21 escenarios en `spec.md` y conservando
> la trazabilidad story↔test en `tasks.md`. Derivada de los tests de `tests/Feature/V2/` y de la
> implementación; es la copia versionada y autoritativa dentro del proyecto.

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

### Article Comments V2 (commit `09918cc`, spec-kit `002-article-comments`)
- `app/Models/Comment.php` + migración `create_comments_table` + `CommentFactory`
- `app/Models/Article.php` — relación `hasMany(Comment)`
- `app/JsonApi/V2/Comments/` — CommentSchema, CommentRequest, CommentAuthorizer
- `app/Policies/CommentPolicy.php` — auth de 3 capas (scope + permiso + ownership);
  el `CommentAuthorizer` delega vía `Gate::inspect`
- `app/Policies/ArticlePolicy.php` — `showComments` (relación read-only)
- `app/JsonApi/V2/Articles/ArticleSchema.php` — `HasMany::make('comments')` read-only
- `app/JsonApi/V2/Server.php` — registra `CommentSchema`
- `app/Providers/AppServiceProvider.php` — scopes `comments:*` en `Passport::tokensCan`
- `routes/api.php` — recurso `comments` + relación `articles.comments`
- Tests (`tests/Feature/V2/`): `Comments/{List,Create,Update,Delete}CommentsTest.php`
  y `Articles/IncludeCommentsTest.php` — **28 tests**

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

V2 base verificada (`/sdd-verify api-v2`, commit `9df2078`) y Article Comments mergeado
(commit `09918cc`). La implementación cumple los escenarios de la spec y la suite (372/372) pasa.

Pendientes opcionales:
- ~~**T026** (spec-kit comments): añadir specs por test bajo `specs/api-v2/Comments/`~~ — **obsoleto**:
  la convención un-archivo-por-test de `specs/api-v2/` se retiró al migrar la V2 base a la nomenclatura
  spec-kit (`specs/001-api-v2-base/`). Comments ya está documentado en `specs/002-article-comments/`.
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
