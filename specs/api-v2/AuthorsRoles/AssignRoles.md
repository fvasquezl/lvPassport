# Authors → Roles (`PATCH /api/v2/authors/{author}/relationships/roles`)

> Spec de [`tests/Feature/V2/AuthorsRoles/AssignRolesTest.php`](../../../tests/Feature/V2/AuthorsRoles/AssignRolesTest.php) · Contexto: [README](../README.md)

Asignación de roles a un autor. Reglas D3 + D4: escrituras requieren `authors:update-roles`
(scope + permission), con bypass de super-admin y guard de auto-remoción.

```gherkin
Scenario: super-admin puede asignar un rol a otro usuario
  Given un super-admin y un rol "capturista" y un usuario objetivo
  When hace PATCH .../roles con [{type: roles, id}]
  Then 2xx  And el objetivo tiene el rol "capturista"

Scenario: super-admin puede reemplazar los roles de otro usuario
  Given un objetivo con rol "viewer"
  When un super-admin hace PATCH .../roles con el rol "editor"
  Then 2xx  And el objetivo tiene "editor" y ya no "viewer"

Scenario: un super-admin NO puede quitarse a sí mismo el rol super-admin
  Given un super-admin actuando sobre sí mismo
  When hace PATCH .../roles con un set que no incluye super-admin
  Then 403  And conserva el rol super-admin

Scenario: un super-admin puede degradar a OTRO super-admin
  Given un super-admin actor y otro usuario super-admin
  When el actor hace PATCH .../roles del otro con [editor]
  Then 2xx  And el otro pierde super-admin y gana editor

Scenario: usuario sin permiso authors:update-roles no puede asignar roles
  Given un usuario con token ['authors:update-roles'] pero sin la permission
  Then PATCH .../roles responde 403

Scenario: invitados no pueden asignar roles
  Then PATCH .../roles responde 401
```
