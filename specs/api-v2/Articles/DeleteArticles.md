# Articles — Borrar (`DELETE /api/v2/articles/{id}`)

> Spec de [`tests/Feature/V2/Articles/DeleteArticlesTest.php`](../../../tests/Feature/V2/Articles/DeleteArticlesTest.php) · Contexto: [README](../README.md)

Autorización: **scope + permission + ownership**.

```gherkin
Scenario: Invitado no puede borrar          → 401, artículo intacto
Scenario: Dueño con scope+permiso borra     → 204, artículo eliminado
Scenario: Sin scope                         → 403, artículo intacto
Scenario: Sin permiso                       → 403, artículo intacto
Scenario: No-dueño                          → 403, artículo intacto
```
