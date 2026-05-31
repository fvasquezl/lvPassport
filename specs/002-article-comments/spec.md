# Feature Specification: Article Comments (V2)

**Feature Branch**: `002-article-comments`

**Created**: 2026-05-29

**Status**: Draft

**Input**: User description: "Comentarios en artículos para la API V2 — recurso `comments` bajo `/api/v2`, pertenece a un artículo y a un autor; lecturas públicas; escrituras con autorización de 3 capas (scope + permiso + ownership); listar los comentarios de un artículo."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Leer los comentarios de un artículo (Priority: P1)

Cualquier consumidor del API (incluido un invitado sin token) puede ver los comentarios asociados
a un artículo, así como un comentario individual, sin necesidad de autenticarse — igual que el
resto de lecturas de la V2.

**Why this priority**: Sin lectura pública no hay valor visible; es el caso de uso más común
(mostrar comentarios bajo un artículo) y habilita todo lo demás.

**Independent Test**: Crear un artículo con comentarios (vía datos de prueba) y comprobar que tanto
un invitado como un usuario autenticado obtienen la lista de comentarios y un comentario individual.

**Acceptance Scenarios**:

1. **Given** un artículo con comentarios, **When** un invitado pide la lista de comentarios del
   artículo, **Then** recibe la colección de comentarios.
2. **Given** un comentario existente, **When** cualquiera (invitado o autenticado) lo solicita por
   su identificador, **Then** recibe el comentario con su cuerpo, fecha y enlaces a su autor y
   artículo.
3. **Given** comentarios existentes, **When** se listan todos los comentarios, **Then** la respuesta
   sigue el mismo formato que los demás recursos de la V2.

---

### User Story 2 - Publicar un comentario en un artículo (Priority: P1)

Un usuario autenticado y autorizado puede publicar un comentario en un artículo, quedando él como
autor del comentario.

**Why this priority**: Es la acción central de la feature; sin crear comentarios el recurso no
aporta valor de escritura.

**Independent Test**: Autenticar a un usuario con el permiso y scope de creación y comprobar que
puede crear un comentario sobre un artículo, que queda registrado con él como autor.

**Acceptance Scenarios**:

1. **Given** un usuario con autorización de creación, **When** publica un comentario con cuerpo
   válido sobre un artículo indicándose a sí mismo como autor, **Then** el comentario se crea y se
   devuelve con su contenido.
2. **Given** un invitado, **When** intenta publicar un comentario, **Then** la petición es rechazada
   por falta de autenticación.
3. **Given** un usuario autenticado pero sin la capacidad de creación (le falta scope o permiso),
   **When** intenta publicar un comentario, **Then** la petición es rechazada y no se crea nada.
4. **Given** un usuario autorizado, **When** intenta crear el comentario indicando a OTRO usuario
   como autor, **Then** la petición es rechazada (no puede publicar en nombre de otro).
5. **Given** un usuario autorizado, **When** envía un comentario con cuerpo vacío, **Then** la
   petición es rechazada por validación y no se crea nada.

---

### User Story 3 - Editar o eliminar el propio comentario (Priority: P2)

El autor de un comentario puede editar su cuerpo o eliminarlo. Nadie puede editar ni borrar
comentarios ajenos (salvo el bypass de super-admin).

**Why this priority**: Completa el ciclo de vida del comentario y la moderación básica por el propio
autor, pero el valor principal (leer/crear) ya se entrega con P1.

**Independent Test**: Con dos usuarios distintos, comprobar que el autor puede actualizar/eliminar su
comentario y que el otro usuario recibe un rechazo al intentarlo sobre el mismo comentario.

**Acceptance Scenarios**:

1. **Given** el autor de un comentario con autorización de edición, **When** actualiza el cuerpo,
   **Then** el cambio se guarda.
2. **Given** el autor de un comentario con autorización de borrado, **When** lo elimina, **Then** el
   comentario deja de existir.
3. **Given** un usuario que no es el autor, **When** intenta editar o eliminar el comentario,
   **Then** la petición es rechazada y el comentario no cambia.
4. **Given** un usuario sin el scope o permiso correspondiente, **When** intenta editar o eliminar
   su propio comentario, **Then** la petición es rechazada.

### Edge Cases

- **Cuerpo vacío o solo espacios**: se rechaza por validación (cuerpo requerido y no vacío).
- **Comentario sobre un artículo inexistente**: la petición no debe crear un comentario huérfano.
- **Publicar en nombre de otro autor**: rechazado por ownership.
- **Editar/eliminar comentario ajeno**: rechazado por ownership.
- **super-admin**: puede gestionar cualquier comentario (bypass de autorización, coherente con el
  resto del sistema).
- **Borrado de un artículo con comentarios**: fuera de alcance de esta feature (ver Assumptions).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST exponer comentarios como un recurso de la API V2, asociado a un
  artículo y a un autor.
- **FR-002**: El sistema MUST permitir leer la lista de comentarios y un comentario individual a
  cualquier consumidor, sin requerir autenticación (lectura pública).
- **FR-003**: El sistema MUST permitir obtener los comentarios de un artículo a través de la relación
  del artículo (solo lectura).
- **FR-004**: Cada comentario MUST exponer su cuerpo, su fecha de creación y de actualización, y los
  vínculos a su autor y a su artículo.
- **FR-005**: El sistema MUST exigir, para crear un comentario, las tres capas de autorización:
  capacidad de creación en el token (scope), permiso del usuario, y que el autor declarado sea el
  propio usuario autenticado (ownership).
- **FR-006**: El sistema MUST impedir que un usuario cree un comentario en nombre de otro autor.
- **FR-007**: El sistema MUST exigir, para editar o eliminar un comentario, las tres capas: scope,
  permiso, y ser el autor del comentario (ownership).
- **FR-008**: El sistema MUST rechazar comentarios cuyo cuerpo esté vacío o ausente.
- **FR-009**: El sistema MUST rechazar con "no autenticado" cualquier escritura realizada sin token.
- **FR-010**: El sistema MUST rechazar con "prohibido" cualquier escritura de un usuario autenticado
  que no cumpla scope, permiso u ownership.
- **FR-011**: El sistema MUST permitir al rol super-admin gestionar cualquier comentario, de forma
  coherente con el bypass de autorización existente.
- **FR-012**: El recurso de comentarios MUST seguir el mismo formato y convenciones que los recursos
  existentes de la V2 (articles, categories).

### Key Entities *(include if feature involves data)*

- **Comment**: representa un comentario sobre un artículo. Atributos: cuerpo (texto), fecha de
  creación, fecha de actualización. Relaciones: pertenece a un **Author** (el usuario que lo
  escribió) y a un **Article**.
- **Article** (existente): gana una relación "tiene muchos comentarios" (solo lectura desde el
  artículo).
- **Author / User** (existente): es el autor de cero o más comentarios.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un invitado puede recuperar los comentarios de un artículo en una sola petición, sin
  autenticarse.
- **SC-002**: Un usuario autorizado puede publicar un comentario y verlo reflejado inmediatamente al
  releer los comentarios del artículo.
- **SC-003**: El 100% de los intentos de escritura sin token se rechazan como "no autenticado", y el
  100% de los intentos sin scope, sin permiso o sin ownership se rechazan como "prohibido".
- **SC-004**: El 100% de los intentos de publicar con cuerpo vacío o en nombre de otro autor se
  rechazan sin crear datos.
- **SC-005**: Ningún cambio de esta feature altera el comportamiento observable de los recursos o
  versiones existentes (la suite previa sigue en verde).

## Assumptions

- **Comentarios planos**: no hay respuestas anidadas ni hilos (un comentario no responde a otro).
- **Cuerpo**: requerido, no vacío; se asume un máximo razonable de 2000 caracteres.
- **Sin moderación adicional**: no hay aprobación, reportes ni estados (publicado/oculto) en v1.
- **Filtros/orden/paginación**: no requeridos en v1; la lista devuelve los comentarios del artículo
  (se podrá añadir paginación después, como en categories).
- **Borrado en cascada al eliminar un artículo**: fuera de alcance de esta feature; se gestionará a
  nivel de datos si se requiere.
- **Solo V2**: la feature se añade únicamente a la V2; V1 no se modifica (versionado sin breakage).
- **Reutiliza la autenticación y el RBAC existentes** (Passport + Spatie) sin cambios estructurales.
