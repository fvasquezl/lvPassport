# Infraestructura

> Spec de [`tests/Feature/V2/InfrastructureTest.php`](../../tests/Feature/V2/InfrastructureTest.php) · Contexto: [README](README.md)

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
