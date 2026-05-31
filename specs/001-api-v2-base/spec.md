# Feature Specification: API v2 Base (versioned JSON:API with hardened auth + RBAC)

**Feature Branch**: `001-api-v2-base`

**Created**: 2026-05-30

**Status**: Implemented (retrofitted spec — reconstructed from `tests/Feature/V2/` and the V2 implementation; the executable tests are the source of truth)

**Input**: User description: "Segunda versión del API JSON:API (`/api/v2`) que convive con V1 sin modificarla, con autenticación endurecida (scopes explícitos, sin wildcard), paridad de recursos con V1 (articles, categories, authors, roles, permissions) y consultas enriquecidas (filtros, orden, paginación) en categories."

> **Nota de origen**: esta spec consolida en un solo documento los 21 escenarios Given-When-Then que
> antes vivían en `specs/api-v2/` (un archivo por test). La trazabilidad story → archivo de test se
> conserva en [`tasks.md`](tasks.md). Si cambias el comportamiento, actualiza spec **y** test.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Una segunda versión del API que no rompe la primera (Priority: P1)

El equipo necesita evolucionar el API sin romper a los consumidores existentes de V1. V2 se expone en
paralelo bajo `/api/v2`, con su propio servidor, sus rutas y su propio login; V1 permanece intacta.

**Why this priority**: Es la base de toda la feature — versionado sin breakage (principio constitucional
IV). Sin esta garantía, ningún consumidor de V1 puede confiar en la evolución del API.

**Independent Test**: Arrancar la app y comprobar que las rutas y el servidor de V2 existen y que las
rutas de V1 siguen registradas y sin cambios.

**Acceptance Scenarios**:

1. **Given** la aplicación arrancada, **When** se inspecciona el registro de servidores, **Then** existe
   el servidor V2 con baseUri `/api/v2` y el scope `read` está declarado.
2. **Given** la app arrancada, **When** se inspeccionan las rutas, **Then** existen las rutas HTTP de V2
   (`login`, `logout`, `user`) y las rutas JSON:API (`articles`, `authors`, `categories`).
3. **Given** la app arrancada, **When** se inspeccionan las rutas de V1, **Then** siguen registradas e
   inalteradas (`login`, `logout`, `user`, `articles.index`, …).

---

### User Story 2 - Autenticarse con privilegio mínimo (scopes explícitos) (Priority: P1)

Un consumidor inicia sesión en V2 pidiendo exactamente los scopes que usará. V2 **nunca** emite tokens
wildcard (`*`); si no se piden scopes, el token recibe solo `read`. El consumidor puede consultar su
identidad y cerrar sesión.

**Why this priority**: La autenticación endurecida es prerequisito de toda escritura y del modelo de
autorización por capas. Es parte del MVP.

**Independent Test**: Hacer login con y sin `scopes`, verificar el contenido del token; consultar `/user`;
hacer `logout` y verificar la revocación.

**Acceptance Scenarios**:

1. **Given** un usuario con credenciales válidas, **When** hace login sin pedir `scopes`, **Then** recibe
   `200` con `{ token, user }` y el token contiene `['read']` y **no** contiene `*`.
2. **Given** un usuario válido, **When** hace login con `scopes: ["read"]`, **Then** recibe `200` con token.
3. **Given** credenciales inválidas, **When** hace login, **Then** recibe `401`.
4. **Given** una petición de login sin `email` o sin `password`, **When** se procesa, **Then** recibe `422`.
5. **Given** un usuario autenticado, **When** hace `GET /user`, **Then** recibe `200` con su email; un
   invitado recibe `401`.
6. **Given** un usuario autenticado, **When** hace `logout`, **Then** recibe `204` y su token queda revocado;
   un invitado recibe `401`.

---

### User Story 3 - Leer contenido públicamente (Priority: P1)

Cualquier consumidor (incluido un invitado o un token sin scope) puede leer artículos y categorías:
listarlos y verlos individualmente, con el formato JSON:API estándar.

**Why this priority**: La lectura pública de contenido es el caso de uso más común y habilita la SPA
cliente; junto con la autenticación forma el núcleo consumible.

**Independent Test**: Como invitado y como token sin scope, listar y mostrar artículos y categorías y
verificar `200` con la estructura JSON:API completa.

**Acceptance Scenarios**:

1. **Given** artículos/categorías existentes, **When** un invitado los lista o muestra, **Then** recibe `200`.
2. **Given** un token sin scope, **When** lista o muestra artículos/categorías, **Then** recibe `200`.
3. **Given** un recurso existente, **When** se solicita, **Then** la respuesta incluye `type`, `attributes`
   (p. ej. `title`, `slug`, `content`, `createdAt`, `updatedAt` para artículos) y `links.self`.
4. **Given** N recursos, **When** se listan, **Then** la colección devuelve los N elementos con su estructura.

---

### User Story 4 - Crear, editar y borrar artículos con autorización de 3 capas (Priority: P1)

Un usuario autorizado gestiona artículos. Toda escritura exige **scope** (Passport) + **permiso** (Spatie)
+ **ownership** (el autor del payload / el dueño del recurso debe ser el propio usuario). El super-admin
salta toda comprobación.

**Why this priority**: Es la acción de escritura central del dominio de contenido y la que ejercita el
modelo de autorización por capas de extremo a extremo.

**Independent Test**: Con un usuario dueño autorizado, crear/editar/borrar su artículo (2xx); con
invitado (401), sin scope/permiso (403) y como no-dueño/otro-autor (403) comprobar el rechazo sin efectos.

**Acceptance Scenarios**:

1. **Given** un usuario con scope+permiso `articles:store` indicándose como autor, **When** crea un artículo
   válido, **Then** recibe `201` y el artículo persiste con su `user_id`.
2. **Given** un invitado, **When** intenta crear, **Then** `401` y no se crea nada.
3. **Given** un usuario con permiso pero sin scope (o con scope pero sin permiso), **When** intenta crear,
   **Then** `403` y no se crea nada.
4. **Given** un usuario autorizado que declara a OTRO usuario como autor, **When** intenta crear, **Then**
   `403` (ownership) y no se crea nada.
5. **Given** un dueño con scope+permiso `articles:update`, **When** actualiza atributos o un solo atributo,
   **Then** `200` y solo cambian los campos enviados; un no-dueño recibe `403`.
6. **Given** un dueño con scope+permiso `articles:update-categories` / `articles:update-authors`, **When**
   hace PATCH sobre la relación correspondiente, **Then** `200` y la relación se reemplaza.
7. **Given** un dueño con scope+permiso `articles:delete`, **When** borra su artículo, **Then** `204`; un
   no-dueño, sin scope o sin permiso recibe `403` y el artículo permanece.
8. **Given** un payload malformado o con atributos desconocidos, **When** se envía, **Then** `400`; con
   atributos requeridos vacíos, slug duplicado o slug inválido, **Then** `422` con el `pointer` correcto.

---

### User Story 5 - Gestionar categorías (scope + permiso, sin ownership) (Priority: P2)

Un usuario autorizado crea, edita y borra categorías. Las categorías no tienen dueño: la autorización es
**scope + permiso** (sin capa de ownership).

**Why this priority**: Completa el CRUD del segundo recurso de contenido; se apoya en el mismo modelo de
autorización pero sin ownership, demostrando esa variante.

**Independent Test**: Con scope+permiso, crear/editar/borrar categorías (2xx); invitado (401), sin
scope/permiso (403); validar `name`/`slug` obligatorios, slug único y formato de slug (422).

**Acceptance Scenarios**:

1. **Given** un usuario con scope+permiso, **When** crea una categoría válida, **Then** `201` y persiste.
2. **Given** un invitado, **When** intenta crear/editar/borrar, **Then** `401` sin efectos.
3. **Given** un usuario sin scope o sin permiso, **When** intenta escribir, **Then** `403` sin efectos.
4. **Given** un usuario autorizado, **When** crea/edita con `name`/`slug` vacío, slug duplicado o slug
   inválido, **Then** `422` con `pointer` a `/data/attributes/{campo}` y sin alterar datos.
5. **Given** un usuario con scope+permiso, **When** borra una categoría, **Then** `204`.

---

### User Story 6 - Descubrir contenido: filtrar, ordenar y paginar (Priority: P2)

Los consumidores acotan los listados con filtros, orden y paginación. Estas consultas son **públicas**
(invitado y token sin scope incluidos). Articles soporta filtros; categories soporta filtros, orden y
paginación.

**Why this priority**: Mejora la experiencia de consumo (la SPA) sobre la lectura básica de US3, pero no
es imprescindible para el MVP.

**Independent Test**: Aplicar cada filtro/orden/paginación como invitado y verificar `200` y los
resultados correctos; un parámetro desconocido devuelve `400`.

**Acceptance Scenarios**:

1. **Given** artículos, **When** se filtra por `title`, `content`, `year`, `month`, `search` (uno o varios
   términos), `categories` o `authors` (uno o varios), **Then** `200` con las coincidencias esperadas.
2. **Given** categorías, **When** se filtra por `name`, `slug` o `search`, **Then** `200` con coincidencias.
3. **Given** categorías, **When** se ordena por `name` o `slug` (asc/desc), **Then** `200` en el orden pedido.
4. **Given** 10 categorías con `page[size]=2&page[number]=3`, **When** se pagina, **Then** `200` con
   `links.first/last/prev/next` correctos (última = número 5).
5. **Given** cualquier listado, **When** se usa un filtro u orden desconocido, **Then** `400 Bad Request`.

---

### User Story 7 - Directorio de autores (lectura con scope) (Priority: P2)

Los consumidores autenticados consultan autores (proyección de solo lectura sobre los usuarios). A
diferencia de articles/categories, las lecturas de autores **requieren** un token con scope `read`. No hay
escritura de autores vía API.

**Why this priority**: Necesario para resolver relaciones de autoría en la SPA, pero secundario frente al
contenido y la autenticación.

**Independent Test**: Como invitado (401), como token sin scope (403) y como token con `read` (200),
listar y mostrar autores.

**Acceptance Scenarios**:

1. **Given** un invitado, **When** lista o muestra un autor, **Then** `401`.
2. **Given** un token sin scope `read`, **When** lista o muestra un autor, **Then** `403`.
3. **Given** un token con scope `read`, **When** lista o muestra autores, **Then** `200` con `type=authors`
   (`name`, `email`, `id` UUID); el listado incluye al propio usuario actuante.
4. **Given** cualquier usuario, **When** intenta una escritura de autores, **Then** la operación está
   prohibida (la política deniega todas las escrituras).

---

### User Story 8 - Control de acceso basado en roles (RBAC) (Priority: P2)

Administradores gestionan roles y consultan permisos, y asignan roles a autores. La autorización usa las
policies globales compartidas con V1 (scope + permiso). El rol `super-admin` es inmutable y existe un guard
de auto-remoción.

**Why this priority**: Habilita la administración del propio sistema de permisos; valioso pero no parte del
flujo de contenido del MVP.

**Independent Test**: Como super-admin, listar/crear/actualizar/borrar roles y listar permisos; verificar la
inmutabilidad de `super-admin`, el rechazo de invitados (401) y de usuarios sin permiso (403), y las reglas
de asignación de roles a autores.

**Acceptance Scenarios**:

1. **Given** un super-admin, **When** lista/crea/actualiza/borra roles (no-`super-admin`), **Then** `200/201/
   200/204`; los roles creados vía API usan `guard_name` `api` por defecto.
2. **Given** el rol `super-admin`, **When** se intenta actualizar o borrar, **Then** `403` y permanece.
3. **Given** un invitado o un usuario sin el permiso correspondiente, **When** lista/crea roles o lista
   permisos, **Then** `401` / `403` respectivamente.
4. **Given** un usuario con scope pero sin el permiso Spatie, **When** intenta crear un rol, **Then** `403`.
5. **Given** un super-admin, **When** asigna o reemplaza los roles de otro usuario (`PATCH
   authors/{id}/relationships/roles` con scope+permiso `authors:update-roles`), **Then** `2xx`.
6. **Given** un super-admin actuando sobre sí mismo, **When** intenta quitarse el rol `super-admin`, **Then**
   `403` y lo conserva; pero **puede** degradar a OTRO super-admin.
7. **Given** un invitado o un usuario sin `authors:update-roles`, **When** intenta asignar roles, **Then**
   `401` / `403`.
8. **Given** el recurso `permissions`, **When** se inspeccionan sus rutas, **Then** es solo lectura (no
   existen `store`/`update`/`destroy`).

### Edge Cases

- **Token wildcard**: V2 nunca emite `['*']`; el fallback sin `scopes` es `['read']`.
- **Autorización precede a validación**: un usuario sin permiso recibe `403` aunque el payload sea inválido
  (p. ej. falte la relación `authors`).
- **Slug**: vacío, con caracteres inválidos, con guion bajo, o que empiece/termine en guion → `422`; slug
  duplicado → `422` sin crear/alterar.
- **Relaciones con tipo incorrecto** (p. ej. `authors` enviado como `categories`) → `422` con pointer.
- **Filtro/orden desconocido** → `400 Bad Request`.
- **super-admin**: salta toda autorización (bypass `Gate::before`), salvo el guard de auto-remoción del
  propio rol `super-admin`.
- **Lecturas de autores sin scope**: invitado `401`, token sin scope `403` (a diferencia de articles/
  categories, cuyas lecturas son públicas).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST exponer una segunda versión del API bajo `/api/v2`, con su propio servidor,
  rutas y login, **sin modificar** el comportamiento observable de V1.
- **FR-002**: El sistema MUST exponer en V2 los recursos `articles`, `categories`, `authors`, `roles` y
  `permissions`, con paridad de comportamiento respecto a V1.
- **FR-003**: El login de V2 MUST emitir tokens con scopes explícitos pedidos por el cliente y MUST NOT
  emitir nunca el scope wildcard `*`; sin scopes en la petición, el token recibe `['read']`.
- **FR-004**: El sistema MUST rechazar el login con credenciales inválidas (`401`) y con `email`/`password`
  ausentes (`422`); MUST permitir consultar el usuario autenticado y cerrar sesión (revocando el token).
- **FR-005**: Las lecturas de `articles` y `categories` (index, show, filtros, orden, paginación) MUST ser
  públicas (invitado y token sin scope incluidos).
- **FR-006**: Las lecturas de `authors` MUST requerir un token con scope `read` (invitado → `401`, token sin
  scope → `403`); el recurso `authors` MUST NOT permitir escrituras vía API.
- **FR-007**: Toda escritura de `articles` MUST exigir las tres capas: scope (Passport) + permiso (Spatie) +
  ownership (el autor del payload / dueño del recurso = usuario autenticado).
- **FR-008**: Toda escritura de `categories` MUST exigir scope + permiso (sin capa de ownership).
- **FR-009**: La reasignación de las relaciones `authors`/`categories` de un artículo MUST requerir los
  scopes/permisos dedicados (`articles:update-authors`, `articles:update-categories`).
- **FR-010**: El sistema MUST devolver `401` ante escrituras sin token y `403` ante escrituras de un usuario
  autenticado que no cumpla scope, permiso u ownership; la autorización MUST evaluarse antes que la validación.
- **FR-011**: El sistema MUST validar los payloads JSON:API: `400` para payload malformado/atributos
  desconocidos/filtro u orden desconocido; `422` con `source.pointer` para campos requeridos vacíos, slug
  duplicado/ inválido y relaciones requeridas ausentes o de tipo incorrecto.
- **FR-012**: `articles` MUST soportar filtros por `title`, `content`, `year`, `month`, `search`,
  `categories` y `authors`; `categories` MUST soportar filtros (`name`, `slug`, `search`), orden (`name`,
  `slug`, `createdAt`, `updatedAt`) y paginación por página.
- **FR-013**: El sistema MUST permitir a administradores el CRUD de `roles` y la lectura de `permissions`,
  autorizado por las policies globales compartidas (scope + permiso); los roles creados vía API MUST usar
  `guard_name` `api` por defecto.
- **FR-014**: El rol `super-admin` MUST ser inmutable (no se puede actualizar ni borrar → `403`).
- **FR-015**: El sistema MUST permitir asignar/reemplazar los roles de un autor con scope+permiso
  `authors:update-roles`, con **bypass de super-admin** y un **guard de auto-remoción** (un super-admin no
  puede quitarse a sí mismo el rol `super-admin`, pero sí puede degradar a otro super-admin).
- **FR-016**: El rol `super-admin` MUST saltar toda comprobación de autorización (bypass global vía
  `Gate::before`), salvo el guard de auto-remoción de FR-015.

### Key Entities *(include if feature involves data)*

- **Article** (existente): contenido con `title`, `slug`, `content`; pertenece a un **Author** (User) y a una
  **Category**. Es el recurso de escritura con ownership.
- **Category** (existente): agrupador con `name`, `slug`; tiene muchos artículos. Recurso de escritura sin
  ownership; soporta filtro/orden/paginación.
- **Author / User** (existente): proyección de solo lectura sobre el usuario (`id` UUID, `name`, `email`); es
  autor de artículos y sujeto de la asignación de roles.
- **Role** (Spatie): rol del guard `api`; CRUD para administradores; `super-admin` inmutable.
- **Permission** (Spatie): permiso del guard `api`; recurso de solo lectura.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: V2 queda disponible bajo `/api/v2` con todos sus recursos y, en paralelo, el 100% de las rutas
  de V1 siguen registradas y con el mismo comportamiento (suite previa en verde).
- **SC-002**: El 100% de los tokens emitidos por el login de V2 carecen del scope wildcard `*`; sin scopes en
  la petición, el token contiene exactamente `['read']`.
- **SC-003**: Un invitado puede leer artículos y categorías (incluyendo filtros/orden/paginación) sin
  autenticarse; las lecturas de autores requieren scope `read`.
- **SC-004**: El 100% de las escrituras sin token se rechazan con `401`, y el 100% de las que no cumplen
  scope, permiso u ownership se rechazan con `403`, sin alterar datos.
- **SC-005**: El 100% de los payloads inválidos (campos requeridos vacíos, slug duplicado/inválido,
  relaciones ausentes/incorrectas, atributos desconocidos, filtro/orden desconocido) se rechazan con el
  código y `pointer` correctos, sin alterar datos.
- **SC-006**: El rol `super-admin` no puede actualizarse ni borrarse, y un super-admin no puede quitarse a sí
  mismo ese rol, en el 100% de los intentos.

## Assumptions

- **Convivencia de versiones**: V1 y V2 conviven indefinidamente; V1 no se modifica (versionado sin breakage).
- **RBAC compartido**: `roles`/`permissions` y `authors.roles` reutilizan las policies globales (`RolePolicy`,
  `PermissionPolicy`) y los permisos de V1; no se crean policies ni permisos nuevos para estos recursos.
- **Lecturas públicas vs. con scope**: articles/categories públicos; authors requiere `read` (decisión de
  diseño explícita, no derivable de V1).
- **Sin comentarios**: el recurso `comments` queda fuera de esta feature (se añade después en
  [`002-article-comments`](../002-article-comments/spec.md)).
- **Guard por defecto**: los roles creados vía API usan `guard_name` `api`.
- **super-admin**: existe un bypass global de autorización y un guard de auto-remoción del propio rol.
