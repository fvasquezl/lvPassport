# Categories — Filtrar (`GET /api/v2/categories?filter[...]`)

> Spec de [`tests/Feature/V2/Categories/FilterCategoriesTest.php`](../../../tests/Feature/V2/Categories/FilterCategoriesTest.php) · Contexto: [README](../README.md)

Filtrado **público**.

```gherkin
Scenario: Invitado / sin scope pueden filtrar           → 200
Scenario: filter[name]    coincidencias por nombre (LIKE)
Scenario: filter[slug]    coincidencias por slug (LIKE)
Scenario: filter[search]  busca por nombre
Scenario: filtro desconocido                            → 400
```
