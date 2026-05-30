# Especificación — API v2

> **Reconstruida desde el código.** La spec original (artefacto Engram `#106`, topic
> `sdd/api-v2/spec`) vivía fuera del repo. Como Engram no está disponible, esta versión se
> derivó de la **fuente de verdad ejecutable**: los tests de `tests/Feature/V2/` y los
> Schemas/Authorizers/Policies de `app/JsonApi/V2/` y `app/Policies/`.
>
> **Formato:** escenarios Given-When-Then. **Un archivo de spec por archivo de test** (ver
> [Trazabilidad](#trazabilidad)). Si cambias el comportamiento, actualiza spec **y** test.

---

## Contexto

V2 es una segunda versión del API JSON:API que **convive con V1 sin modificarla**. Servidor
`App\JsonApi\V2\Server` con baseUri `/api/v2`. Recursos: **articles**, **categories**,
**authors**, **roles** y **permissions** — paridad de recursos con V1.

### Modelo de autorización V2

Tres reglas, evaluadas con AND donde apliquen. Si falta cualquiera → falla.

| Capa | Pregunta | Cómo se consulta |
|------|----------|------------------|
| **Scope** (Passport) | ¿El token puede hacer esto? | `tokenCan('...')` |
| **Permission** (Spatie) | ¿El usuario puede hacer esto? | `hasPermissionTo('...')` |
| **Ownership** | ¿El recurso es del usuario? | comparación de IDs |

### Convenciones de respuesta

| Situación | Código |
|-----------|--------|
| Sin token donde se requiere autenticación | **401** Unauthorized |
| Con token pero sin scope/permission/ownership | **403** Forbidden |
| Payload inválido (validación) | **422** Unprocessable / **400** Bad Request (JSON:API malformado o filtro/sort desconocido) |
| Lectura OK | **200** |
| Creación OK | **201** |
| Update OK | **200** |
| Delete OK | **204** No Content |

### Reglas transversales de lectura

- **articles** y **categories**: las lecturas (`index`, `show`, `filter`, `sort`, `paginate`)
  son **públicas** — funcionan para invitados y para tokens sin scope.
- **authors**: las lecturas **requieren** un token con scope `read` (invitado → 401, token sin
  scope → 403).
- Login V2 **nunca** emite tokens wildcard `['*']`; sin `scopes` en el request, usa `['read']`.

### RBAC en V2 — decisiones de diseño

> Los recursos `roles`/`permissions` y la relación `authors.roles` se llevan a V2 con **paridad
> de comportamiento** respecto a V1. Decisiones:
>
> - **D1 — Policies compartidas.** `roles`/`permissions` reutilizan las policies globales
>   `RolePolicy` y `PermissionPolicy` (registradas en `AppServiceProvider`, no por versión).
>   Misma autorización que V1: `tokenCan('roles:*' / 'permissions:*')` **y**
>   `hasPermissionTo(...)`. No se crean policies nuevas ni permisos nuevos (se reusan los de V1).
> - **D2 — super-admin inmutable.** El rol `super-admin` no se puede actualizar ni borrar
>   (ni vía atributos ni vía relación) → **403**.
> - **D3 — authors.roles.** Lecturas requieren `tokenCan('authors:show-roles')`. Escrituras
>   requieren `authors:update-roles` (scope + permission), con **bypass de super-admin** y el
>   **guard de auto-remoción** (un super-admin no puede quitarse a sí mismo ese rol).
> - **D4 — Gate::before.** El bypass global de `super-admin` aplica a todo lo anterior.
> - Todas las rutas RBAC de V2 van bajo `auth:api` (igual que V1).

---

## Trazabilidad

Cada archivo de spec corresponde 1:1 con un archivo de test (la fuente de verdad ejecutable):

| Spec | Test |
|------|------|
| [Infrastructure](Infrastructure.md) | `tests/Feature/V2/InfrastructureTest.php` |
| [Auth/Login](Auth/Login.md) | `tests/Feature/V2/Auth/LoginTest.php` |
| [Auth/Logout](Auth/Logout.md) | `tests/Feature/V2/Auth/LogoutTest.php` |
| [Auth/AuthenticatedUser](Auth/AuthenticatedUser.md) | `tests/Feature/V2/Auth/AuthenticatedUserTest.php` |
| [Auth/AuthorPolicy](Auth/AuthorPolicy.md) | `tests/Feature/V2/Auth/AuthorPolicyTest.php` |
| [Articles/CreateArticles](Articles/CreateArticles.md) | `tests/Feature/V2/Articles/CreateArticlesTest.php` |
| [Articles/ListArticles](Articles/ListArticles.md) | `tests/Feature/V2/Articles/ListArticlesTest.php` |
| [Articles/UpdateArticles](Articles/UpdateArticles.md) | `tests/Feature/V2/Articles/UpdateArticlesTest.php` |
| [Articles/DeleteArticles](Articles/DeleteArticles.md) | `tests/Feature/V2/Articles/DeleteArticlesTest.php` |
| [Articles/FilterArticles](Articles/FilterArticles.md) | `tests/Feature/V2/Articles/FilterArticlesTest.php` |
| [Categories/CreateCategories](Categories/CreateCategories.md) | `tests/Feature/V2/Categories/CreateCategoriesTest.php` |
| [Categories/ListCategories](Categories/ListCategories.md) | `tests/Feature/V2/Categories/ListCategoriesTest.php` |
| [Categories/UpdateCategories](Categories/UpdateCategories.md) | `tests/Feature/V2/Categories/UpdateCategoriesTest.php` |
| [Categories/DeleteCategories](Categories/DeleteCategories.md) | `tests/Feature/V2/Categories/DeleteCategoriesTest.php` |
| [Categories/FilterCategories](Categories/FilterCategories.md) | `tests/Feature/V2/Categories/FilterCategoriesTest.php` |
| [Categories/SortCategories](Categories/SortCategories.md) | `tests/Feature/V2/Categories/SortCategoriesTest.php` |
| [Categories/PaginateCategories](Categories/PaginateCategories.md) | `tests/Feature/V2/Categories/PaginateCategoriesTest.php` |
| [Authors/ListAuthors](Authors/ListAuthors.md) | `tests/Feature/V2/Authors/ListAuthorsTest.php` |
| [Roles/RolesCrud](Roles/RolesCrud.md) | `tests/Feature/V2/Roles/RolesCrudTest.php` |
| [Permissions/IndexPermissions](Permissions/IndexPermissions.md) | `tests/Feature/V2/Permissions/IndexPermissionsTest.php` |
| [AuthorsRoles/AssignRoles](AuthorsRoles/AssignRoles.md) | `tests/Feature/V2/AuthorsRoles/AssignRolesTest.php` |

Implementación: `app/JsonApi/V2/` (Schemas, Requests, Authorizers), `app/Policies/AuthorPolicy.php`,
`app/Http/Controllers/Api/V2/LoginController.php`, `routes/api.php`, `config/jsonapi.php`.

Para correr solo la suite V2:

```bash
vendor/bin/sail artisan test --compact --filter=V2
```
