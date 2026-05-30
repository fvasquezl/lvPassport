# Login (`POST /api/v2/login`)

> Spec de [`tests/Feature/V2/Auth/LoginTest.php`](../../../tests/Feature/V2/Auth/LoginTest.php) · Contexto: [README](../README.md)

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
