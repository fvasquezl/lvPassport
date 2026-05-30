# Categories — Actualizar (`PATCH /api/v2/categories/{id}`)

> Spec de [`tests/Feature/V2/Categories/UpdateCategoriesTest.php`](../../../tests/Feature/V2/Categories/UpdateCategoriesTest.php) · Contexto: [README](../README.md)

Autorización: **scope + permission**.

```gherkin
Scenario: Invitado no puede actualizar                  → 401, intacta
Scenario: Con scope+permiso actualiza name y slug       → 200
Scenario Outline: actualizar un solo atributo           → 200
  Examples: { name } | { slug }
Scenario: Sin scope                                     → 403, intacta
Scenario: Sin permiso                                   → 403, intacta
Scenario: slug duplicado en update                      → 422 (pointer slug), intacta
Scenario: slug con formato inválido en update           → 422 (pointer slug), intacta
```
