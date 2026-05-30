# Articles — Leer / Listar (`GET /api/v2/articles`)

> Spec de [`tests/Feature/V2/Articles/ListArticlesTest.php`](../../../tests/Feature/V2/Articles/ListArticlesTest.php) · Contexto: [README](../README.md)

Lecturas **públicas** (invitado y token sin scope incluidos).

```gherkin
Scenario: Invitado puede ver un artículo
  Then GET /articles/{id} responde 200

Scenario: Usuario con scope read ve un artículo con su estructura JSON:API completa
  Then 200 con type=articles, attributes (title, slug, content, createdAt, updatedAt) y links.self

Scenario: Usuario sin scope puede ver un artículo
  Then 200

Scenario: Invitado puede listar todos los artículos
  Then GET /articles responde 200

Scenario: Usuario sin scope puede listar
  Then 200

Scenario: Listar devuelve la colección completa
  Given 3 artículos y token ['read']
  Then 200 con 3 elementos y su estructura JSON:API
```
