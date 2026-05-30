# Logout (`POST /api/v2/logout`)

> Spec de [`tests/Feature/V2/Auth/LogoutTest.php`](../../../tests/Feature/V2/Auth/LogoutTest.php) · Contexto: [README](../README.md)

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
