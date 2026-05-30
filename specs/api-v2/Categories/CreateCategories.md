# Categories — Crear (`POST /api/v2/categories`)

> Spec de [`tests/Feature/V2/Categories/CreateCategoriesTest.php`](../../../tests/Feature/V2/Categories/CreateCategoriesTest.php) · Contexto: [README](../README.md)

Sin ownership. Autorización: **scope + permission**.

```gherkin
Scenario: Invitado no puede crear                       → 401, nada creado
Scenario: Sin scope                                     → 403, nada creado
Scenario: Sin permiso                                   → 403, nada creado
Scenario: Con scope+permiso crea la categoría           → 201, existe en BD
Scenario: name es obligatorio          → 422, pointer /data/attributes/name
Scenario: slug es obligatorio          → 422, pointer /data/attributes/slug
Scenario: slug debe ser único          → 422, pointer /data/attributes/slug, sigue habiendo 1
Scenario Outline: slug inválido        → 422, pointer /data/attributes/slug
  Examples (slug): "%$%#@" | "with_underscores" | "-start-with-dash" | "end-with-dash-"
```
