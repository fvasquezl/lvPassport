# Usuario autenticado (`GET /api/v2/user`)

> Spec de [`tests/Feature/V2/Auth/AuthenticatedUserTest.php`](../../../tests/Feature/V2/Auth/AuthenticatedUserTest.php) · Contexto: [README](../README.md)

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
