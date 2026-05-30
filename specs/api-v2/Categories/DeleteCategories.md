# Categories — Borrar (`DELETE /api/v2/categories/{id}`)

> Spec de [`tests/Feature/V2/Categories/DeleteCategoriesTest.php`](../../../tests/Feature/V2/Categories/DeleteCategoriesTest.php) · Contexto: [README](../README.md)

Autorización: **scope + permission**.

```gherkin
Scenario: Invitado no puede borrar                      → 401, intacta
Scenario: Sin scope                                     → 403, intacta
Scenario: Sin permiso                                   → 403, intacta
Scenario: Con scope+permiso borra                       → 204, eliminada
```
