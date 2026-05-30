# Categories — Ordenar (`GET /api/v2/categories?sort=`)

> Spec de [`tests/Feature/V2/Categories/SortCategoriesTest.php`](../../../tests/Feature/V2/Categories/SortCategoriesTest.php) · Contexto: [README](../README.md)

Orden **público**.

```gherkin
Scenario: Invitado / sin scope pueden ordenar           → 200
Scenario Outline: ordenar por nombre
  Examples: sort=name (A,B,C asc) | sort=-name (C,B,A desc)
Scenario Outline: ordenar por slug
  Examples: sort=slug (asc) | sort=-slug (desc)
Scenario: orden por campo desconocido                   → 400
```
