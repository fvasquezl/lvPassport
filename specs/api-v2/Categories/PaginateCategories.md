# Categories — Paginar (`GET /api/v2/categories?page[...]`)

> Spec de [`tests/Feature/V2/Categories/PaginateCategoriesTest.php`](../../../tests/Feature/V2/Categories/PaginateCategoriesTest.php) · Contexto: [README](../README.md)

Paginación **pública**.

```gherkin
Scenario: Invitado / sin scope pueden paginar           → 200
Scenario: Paginación con size y number
  Given 10 categorías, page[size]=2 page[number]=3
  Then 200 con links.first/last/prev/next correctos (last = number 5)
```
