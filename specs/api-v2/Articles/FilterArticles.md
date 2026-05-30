# Articles — Filtrar (`GET /api/v2/articles?filter[...]`)

> Spec de [`tests/Feature/V2/Articles/FilterArticlesTest.php`](../../../tests/Feature/V2/Articles/FilterArticlesTest.php) · Contexto: [README](../README.md)

Filtrado **público**.

```gherkin
Scenario: Invitado / sin scope pueden filtrar      → 200
Scenario: filter[title]    devuelve coincidencias por título (LIKE)
Scenario: filter[content]  devuelve coincidencias por contenido (LIKE)
Scenario: filter[year]     devuelve artículos del año dado (por created_at)
Scenario: filter[month]    devuelve artículos del mes dado
Scenario: filter[search]   busca en título y contenido (un término)
Scenario: filter[search]   busca con múltiples términos (OR por término)
Scenario: filter[categories] = id            → artículos de esa categoría
Scenario: filter[categories] = "id1,id2"     → artículos de varias categorías
Scenario: filter[authors] = nombre           → artículos de ese autor
Scenario: filter[authors] = "n1,n2"          → artículos de varios autores
Scenario: filtro desconocido                 → 400 Bad Request
```
