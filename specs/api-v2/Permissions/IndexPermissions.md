# Permissions (`/api/v2/permissions`)

> Spec de [`tests/Feature/V2/Permissions/IndexPermissionsTest.php`](../../../tests/Feature/V2/Permissions/IndexPermissionsTest.php) · Contexto: [README](../README.md)

Solo lectura (`index`, `show`). Autorización vía `PermissionPolicy` (scope + permission).

```gherkin
Scenario: super-admin puede listar permissions
  Given un super-admin y permissions existentes
  When hace GET /api/v2/permissions
  Then 200  And data.0.type == "permissions"

Scenario: invitados no pueden listar permissions
  Then GET /api/v2/permissions responde 401

Scenario: usuarios sin permiso no pueden listar permissions
  Given un usuario autenticado sin permiso
  Then 403

Scenario: el recurso permissions no expone rutas de escritura
  Then no existen api.v2.permissions.store / update / destroy
```
