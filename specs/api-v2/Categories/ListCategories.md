# Categories — Leer / Listar (`GET /api/v2/categories`)

> Spec de [`tests/Feature/V2/Categories/ListCategoriesTest.php`](../../../tests/Feature/V2/Categories/ListCategoriesTest.php) · Contexto: [README](../README.md)

Lecturas **públicas**.

```gherkin
Scenario: Invitado ve una categoría                     → 200
Scenario: Usuario con read ve una categoría             → 200 con estructura JSON:API
Scenario: Usuario sin scope ve una categoría            → 200
Scenario: Invitado lista categorías                     → 200
Scenario: Usuario sin scope lista categorías            → 200
Scenario: Listado completo con read                     → 200 con N elementos
```
