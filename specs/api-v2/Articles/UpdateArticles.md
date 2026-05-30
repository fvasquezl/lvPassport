# Articles — Actualizar (`PATCH /api/v2/articles/{id}`)

> Spec de [`tests/Feature/V2/Articles/UpdateArticlesTest.php`](../../../tests/Feature/V2/Articles/UpdateArticlesTest.php) · Contexto: [README](../README.md)

Autorización: **scope + permission + ownership**.

```gherkin
Scenario: Invitado no puede actualizar
  Then 401  And el artículo no cambia

Scenario: Dueño con scope+permiso actualiza su artículo
  Given usuario dueño con scope ['articles:update'] y permiso articles:update
  Then 200  And los cambios persisten

Scenario: Sin scope → 403  (artículo intacto)
Scenario: Sin permiso → 403  (artículo intacto)
Scenario: No-dueño → 403  (artículo intacto, ownership)

Scenario Outline: actualizar un solo atributo
  Given dueño con scope+permiso
  Then 200  And solo cambia <attribute>
  Examples: { title } | { slug }

Scenario: Reemplazar la categoría de un artículo
  Given dueño con scope ['articles:update-categories'] y permiso articles:update-categories
  When PATCH /articles/{id}/relationships/categories con una categoría nueva
  Then 200  And category_id se actualiza

Scenario: Reemplazar el autor de un artículo
  Given dueño con scope ['articles:update-authors'] y permiso articles:update-authors
  When PATCH /articles/{id}/relationships/authors con un autor nuevo
  Then 200  And user_id se actualiza
```
