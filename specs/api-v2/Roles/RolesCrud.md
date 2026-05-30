# Roles (`/api/v2/roles`)

> Spec de [`tests/Feature/V2/Roles/RolesCrudTest.php`](../../../tests/Feature/V2/Roles/RolesCrudTest.php) · Contexto: [README](../README.md)

CRUD de roles Spatie. Autorización vía `RolePolicy` (scope + permission). Rutas `auth:api`.
super-admin inmutable (D2).

```gherkin
Scenario: super-admin puede listar roles
  Given un super-admin autenticado y roles existentes
  When hace GET /api/v2/roles
  Then 200  And data.0.type == "roles"

Scenario: super-admin puede crear un rol
  Given un super-admin autenticado
  When hace POST /api/v2/roles con name = "new-role"
  Then 201 con attributes.name == "new-role"  And existe en BD con guard_name "api"

Scenario: roles creados vía API usan guard_name "api" por defecto
  Given un super-admin autenticado
  When crea un rol sin especificar guard
  Then 201  And el rol queda con guard_name "api"

Scenario: super-admin puede actualizar un rol que no es super-admin
  Given un super-admin y un rol "to-rename"
  When hace PATCH /api/v2/roles/{id} con name = "renamed"
  Then 200  And el rol queda renombrado

Scenario: super-admin puede borrar un rol que no es super-admin
  Given un super-admin y un rol "disposable"
  When hace DELETE /api/v2/roles/{id}
  Then 204  And el rol ya no existe

Scenario: el rol super-admin no se puede actualizar
  Given un super-admin y el rol "super-admin"
  When intenta PATCH sobre el rol super-admin
  Then 403  And el rol sigue llamándose "super-admin"

Scenario: el rol super-admin no se puede borrar
  Given un super-admin y el rol "super-admin"
  When intenta DELETE sobre el rol super-admin
  Then 403  And el rol sigue existiendo

Scenario: invitados no pueden listar roles
  Then GET /api/v2/roles responde 401

Scenario: usuario sin permiso roles:index no puede listar
  Given un usuario autenticado sin el permiso
  Then 403

Scenario: usuario con scope pero sin permiso no puede crear roles
  Given un usuario con token ['roles:store'] pero sin la permission Spatie
  When hace POST /api/v2/roles
  Then 403  And no se crea el rol

Scenario: miembros del rol admin pueden listar roles
  Given un usuario con rol admin y permiso roles:index, token ['roles:index']
  Then GET /api/v2/roles responde 200
```
