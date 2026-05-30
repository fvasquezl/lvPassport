# Especificación — API v2

> **Reconstruida desde el código.** La spec original (67 escenarios, artefacto Engram `#106`,
> topic `sdd/api-v2/spec`) vivía fuera del repo. Como Engram no está disponible, esta versión
> se derivó de la **fuente de verdad ejecutable**: los tests de `tests/Feature/V2/` y los
> Schemas/Authorizers/Policies de `app/JsonApi/V2/` y `app/Policies/`.
>
> **Formato:** escenarios Given-When-Then. Cada escenario mapea 1:1 a un test Pest (ver
> [Trazabilidad](#trazabilidad)). Si cambias el comportamiento, actualiza spec **y** test.

---

## Contexto

V2 es una segunda versión del API JSON:API que **convive con V1 sin modificarla**. Servidor
`App\JsonApi\V2\Server` con baseUri `/api/v2`. Recursos: **articles**, **categories**,
**authors** (no expone roles ni permissions — eso es exclusivo de V1).

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

---

## Feature 1 — Infraestructura

```gherkin
Scenario: El servidor V2 está registrado
  Given la aplicación arrancada
  Then la clase App\JsonApi\V2\Server existe
  And el scope "read" está declarado en Passport::tokensCan

Scenario: Las rutas HTTP de V2 existen
  Then existe la ruta POST /api/v2/login   nombrada api.v2.login
  And  existe la ruta POST /api/v2/logout  nombrada api.v2.logout
  And  existe la ruta GET  /api/v2/user    nombrada api.v2.user

Scenario: Las rutas JSON:API de V2 existen
  Then existen api.v2.articles.index, api.v2.authors.index, api.v2.categories.index

Scenario: V2 no rompe V1
  Then siguen registradas api.v1.login, api.v1.logout, api.v1.user, api.v1.articles.index
```

---

## Feature 2 — Autenticación

### Login (`POST /api/v2/login`)

```gherkin
Scenario: Login con credenciales válidas, sin scopes → scope read por defecto
  Given un usuario con password conocido
  When hace POST /api/v2/login con email y password correctos (sin "scopes")
  Then responde 200 con { token, user } y user.email correcto
  And el token almacenado contiene ['read'] y NO contiene '*'

Scenario: Login con scopes explícitos
  Given un usuario válido
  When hace POST /api/v2/login con "scopes": ["read"]
  Then responde 200 con { token }

Scenario: Login con credenciales inválidas
  When hace POST /api/v2/login con password incorrecto
  Then responde 401

Scenario: email es obligatorio
  When hace POST /api/v2/login sin email
  Then responde 422

Scenario: password es obligatorio
  When hace POST /api/v2/login sin password
  Then responde 422
```

### Logout (`POST /api/v2/logout`)

```gherkin
Scenario: Logout revoca el token actual
  Given un usuario autenticado con bearer token
  When hace POST /api/v2/logout
  Then responde 204
  And el token queda revoked = true

Scenario: Invitado no puede hacer logout
  When un invitado hace POST /api/v2/logout
  Then responde 401
```

### Usuario autenticado (`GET /api/v2/user`)

```gherkin
Scenario: Obtener el usuario autenticado
  Given un usuario autenticado
  When hace GET /api/v2/user
  Then responde 200 con su email

Scenario: /user no exige un scope específico
  Given un usuario con token de scope ['read']
  When hace GET /api/v2/user
  Then responde 200

Scenario: Invitado no puede consultar /user
  Then responde 401
```

---

## Feature 3 — Autorización de autores (`AuthorPolicy`)

> Decisión de diseño V2: las lecturas de autores usan `tokenCan('read')` **únicamente** (sin
> permission Spatie). Toda escritura de autores está prohibida vía API.

```gherkin
Scenario: viewAny permitido con scope read
  Given un usuario con token de scope ['read']
  Then Gate::allows('viewAny', User::class) es true

Scenario: viewAny denegado sin scope read
  Given un usuario con token sin scopes
  Then AuthorPolicy::viewAny es false

Scenario: view permitido con scope read
  Given un usuario con token ['read'] y un autor objetivo
  Then Gate::allows('view', $author) es true

Scenario: view denegado sin scope read
  Then AuthorPolicy::view es false

Scenario Outline: las escrituras de autores siempre se deniegan
  Then AuthorPolicy::<action> es false
  Examples: create | update | delete
```

---

## Feature 4 — Articles (`/api/v2/articles`)

### Crear (`POST`) — scope + permission + ownership

```gherkin
Scenario: Invitado no puede crear
  When un invitado hace POST /articles
  Then responde 401  And no se crea nada

Scenario: Crear sin "data" → error JSON:API
  Given usuario con scope+permission articles:store
  When envía data vacío
  Then responde 400  And errors.0.source.pointer == "/data"

Scenario: Crear correctamente
  Given usuario con scope ['articles:store'] y permiso articles:store,
        siendo el autor del payload el propio usuario
  When hace POST /articles con título, slug, content y relaciones authors+categories
  Then responde 201 con los atributos enviados
  And el artículo existe en BD con user_id del autor

Scenario: Sin scope → 403
  Given usuario con permiso pero token sin scope articles:store
  Then responde 403  And no se crea nada

Scenario: Sin permiso → 403
  Given usuario con scope articles:store pero sin el permiso Spatie
  Then responde 403  And no se crea nada

Scenario: Crear a nombre de otro usuario → 403 (ownership)
  Given usuario con scope+permiso, pero authors.data.id apunta a OTRO usuario
  Then responde 403  And no se crea nada

Scenario: Sin permiso, 403 aunque falte la relación authors
  Given usuario con scope pero sin permiso, y payload sin authors
  Then responde 403  (la autorización precede a la validación)

Scenario: Rechaza atributos desconocidos
  Given usuario con scope+permiso y un atributo "approved" extra
  Then responde 400  And no se crea nada

Scenario: authors es obligatorio
  Given usuario con scope+permiso y payload sin authors
  Then responde 422  And errors.0.source.pointer == "/data/relationships/authors"

Scenario: categories es obligatorio
  Given usuario con scope+permiso y payload sin categories
  Then responde 422  And errors.0.source.pointer == "/data/relationships/categories"

Scenario Outline: las relaciones deben ser del tipo correcto
  Given usuario con scope+permiso
  When <relationship> se envía con tipo <wrongType>
  Then responde 422  And el pointer apunta a /data/relationships/<relationship>
  Examples:
    | relationship | wrongType  |
    | authors      | categories |
    | categories   | authors    |

Scenario Outline: atributos obligatorios no pueden ir vacíos
  Given usuario con scope+permiso y <field> = ""
  Then responde 422  And pointer == /data/attributes/<field>
  Examples: title | content

Scenario: slug debe ser único
  Given ya existe un artículo con slug "same-slug"
  When se crea otro con el mismo slug
  Then responde 422 con pointer /data/attributes/slug  And sigue habiendo 1 artículo

Scenario Outline: slug inválido se rechaza
  Then responde 422 con pointer /data/attributes/slug
  Examples (slug): "" | "%$%#@" | "with_underscores" | "-start-with-dash" | "end-with-dash-"
```

### Leer / Listar (`GET`) — público

```gherkin
Scenario: Invitado puede ver un artículo
  Then GET /articles/{id} responde 200

Scenario: Usuario con scope read ve un artículo con su estructura JSON:API completa
  Then 200 con type=articles, attributes (title, slug, content, createdAt, updatedAt) y links.self

Scenario: Usuario sin scope puede ver un artículo
  Then 200

Scenario: Invitado puede listar todos los artículos
  Then GET /articles responde 200

Scenario: Usuario sin scope puede listar
  Then 200

Scenario: Listar devuelve la colección completa
  Given 3 artículos y token ['read']
  Then 200 con 3 elementos y su estructura JSON:API
```

### Actualizar (`PATCH`) — scope + permission + ownership

```gherkin
Scenario: Invitado no puede actualizar
  Then 401  And el artículo no cambia

Scenario: Dueño con scope+permiso actualiza su artículo
  Given usuario dueño con scope ['articles:update'] y permiso articles:update
  Then 200  And los cambios persisten

Scenario: Sin scope → 403  (artículo intacto)
Scenario: Sin permiso → 403  (artículo intacto)
Scenario: No-dueño → 403  (artículo intacto, ownership)

Scenario Outline: actualizar un solo atributo
  Given dueño con scope+permiso
  Then 200  And solo cambia <attribute>
  Examples: { title } | { slug }

Scenario: Reemplazar la categoría de un artículo
  Given dueño con scope ['articles:update-categories'] y permiso articles:update-categories
  When PATCH /articles/{id}/relationships/categories con una categoría nueva
  Then 200  And category_id se actualiza

Scenario: Reemplazar el autor de un artículo
  Given dueño con scope ['articles:update-authors'] y permiso articles:update-authors
  When PATCH /articles/{id}/relationships/authors con un autor nuevo
  Then 200  And user_id se actualiza
```

### Borrar (`DELETE`) — scope + permission + ownership

```gherkin
Scenario: Invitado no puede borrar          → 401, artículo intacto
Scenario: Dueño con scope+permiso borra     → 204, artículo eliminado
Scenario: Sin scope                         → 403, artículo intacto
Scenario: Sin permiso                       → 403, artículo intacto
Scenario: No-dueño                          → 403, artículo intacto
```

### Filtrar (`GET ?filter[...]`) — público

```gherkin
Scenario: Invitado / sin scope pueden filtrar      → 200
Scenario: filter[title]    devuelve coincidencias por título (LIKE)
Scenario: filter[content]  devuelve coincidencias por contenido (LIKE)
Scenario: filter[year]     devuelve artículos del año dado (por created_at)
Scenario: filter[month]    devuelve artículos del mes dado
Scenario: filter[search]   busca en título y contenido (un término)
Scenario: filter[search]   busca con múltiples términos (OR por término)
Scenario: filter[categories] = id            → artículos de esa categoría
Scenario: filter[categories] = "id1,id2"     → artículos de varias categorías
Scenario: filter[authors] = nombre           → artículos de ese autor
Scenario: filter[authors] = "n1,n2"          → artículos de varios autores
Scenario: filtro desconocido                 → 400 Bad Request
```

---

## Feature 5 — Categories (`/api/v2/categories`)

> Sin ownership (las categorías no pertenecen a un usuario). Escrituras = scope + permission.
> Lecturas = públicas.

### Crear (`POST`) — scope + permission

```gherkin
Scenario: Invitado no puede crear                       → 401, nada creado
Scenario: Sin scope                                     → 403, nada creado
Scenario: Sin permiso                                   → 403, nada creado
Scenario: Con scope+permiso crea la categoría           → 201, existe en BD
Scenario: name es obligatorio          → 422, pointer /data/attributes/name
Scenario: slug es obligatorio          → 422, pointer /data/attributes/slug
Scenario: slug debe ser único          → 422, pointer /data/attributes/slug, sigue habiendo 1
Scenario Outline: slug inválido        → 422, pointer /data/attributes/slug
  Examples (slug): "%$%#@" | "with_underscores" | "-start-with-dash" | "end-with-dash-"
```

### Leer / Listar — público

```gherkin
Scenario: Invitado ve una categoría                     → 200
Scenario: Usuario con read ve una categoría             → 200 con estructura JSON:API
Scenario: Usuario sin scope ve una categoría            → 200
Scenario: Invitado lista categorías                     → 200
Scenario: Usuario sin scope lista categorías            → 200
Scenario: Listado completo con read                     → 200 con N elementos
```

### Actualizar (`PATCH`) — scope + permission

```gherkin
Scenario: Invitado no puede actualizar                  → 401, intacta
Scenario: Con scope+permiso actualiza name y slug       → 200
Scenario Outline: actualizar un solo atributo           → 200
  Examples: { name } | { slug }
Scenario: Sin scope                                     → 403, intacta
Scenario: Sin permiso                                   → 403, intacta
Scenario: slug duplicado en update                      → 422 (pointer slug), intacta
Scenario: slug con formato inválido en update           → 422 (pointer slug), intacta
```

### Borrar (`DELETE`) — scope + permission

```gherkin
Scenario: Invitado no puede borrar                      → 401, intacta
Scenario: Sin scope                                     → 403, intacta
Scenario: Sin permiso                                   → 403, intacta
Scenario: Con scope+permiso borra                       → 204, eliminada
```

### Filtrar — público

```gherkin
Scenario: Invitado / sin scope pueden filtrar           → 200
Scenario: filter[name]    coincidencias por nombre (LIKE)
Scenario: filter[slug]    coincidencias por slug (LIKE)
Scenario: filter[search]  busca por nombre
Scenario: filtro desconocido                            → 400
```

### Ordenar (`GET ?sort=`) — público

```gherkin
Scenario: Invitado / sin scope pueden ordenar           → 200
Scenario Outline: ordenar por nombre
  Examples: sort=name (A,B,C asc) | sort=-name (C,B,A desc)
Scenario Outline: ordenar por slug
  Examples: sort=slug (asc) | sort=-slug (desc)
Scenario: orden por campo desconocido                   → 400
```

### Paginar (`GET ?page[...]`) — público

```gherkin
Scenario: Invitado / sin scope pueden paginar           → 200
Scenario: Paginación con size y number
  Given 10 categorías, page[size]=2 page[number]=3
  Then 200 con links.first/last/prev/next correctos (last = number 5)
```

---

## Feature 6 — Authors (`/api/v2/authors`)

> Recurso de solo lectura sobre `User`. Lecturas **requieren** scope `read`.

```gherkin
Scenario: Invitado no puede ver un autor                → 401
Scenario: Usuario con read ve un autor                  → 200 (type=authors, name, email, id UUID)
Scenario: Usuario sin scope read no puede ver un autor  → 403
Scenario: Invitado no puede listar autores              → 401
Scenario: Usuario sin scope no puede listar autores     → 403
Scenario: Usuario con read lista autores                → 200 (incluye al usuario actuante)
```

---

## Trazabilidad

Cada feature mapea a archivos de test (la fuente de verdad ejecutable):

| Feature | Archivo de test |
|---------|-----------------|
| 1 — Infraestructura | `tests/Feature/V2/InfrastructureTest.php` |
| 2 — Login / Logout / User | `tests/Feature/V2/Auth/{LoginTest,LogoutTest,AuthenticatedUserTest}.php` |
| 3 — AuthorPolicy | `tests/Feature/V2/Auth/AuthorPolicyTest.php` |
| 4 — Articles | `tests/Feature/V2/Articles/{Create,Update,Delete,List,Filter}ArticlesTest.php` |
| 5 — Categories | `tests/Feature/V2/Categories/{Create,Update,Delete,List,Filter,Sort,Paginate}CategoriesTest.php` |
| 6 — Authors | `tests/Feature/V2/Authors/ListAuthorsTest.php` |

Implementación: `app/JsonApi/V2/` (Schemas, Requests, Authorizers), `app/Policies/AuthorPolicy.php`,
`app/Http/Controllers/Api/V2/LoginController.php`, `routes/api.php`, `config/jsonapi.php`.

Para correr solo la suite V2:

```bash
vendor/bin/sail artisan test --compact --filter=V2
```
