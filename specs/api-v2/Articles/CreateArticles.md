# Articles — Crear (`POST /api/v2/articles`)

> Spec de [`tests/Feature/V2/Articles/CreateArticlesTest.php`](../../../tests/Feature/V2/Articles/CreateArticlesTest.php) · Contexto: [README](../README.md)

Autorización: **scope + permission + ownership** (el autor del payload debe ser el usuario autenticado).

```gherkin
Scenario: Invitado no puede crear
  When un invitado hace POST /articles
  Then responde 401  And no se crea nada

Scenario: Crear sin "data" → error JSON:API
  Given usuario con scope+permission articles:store
  When envía data vacío
  Then responde 400  And errors.0.source.pointer == "/data"

Scenario: Crear correctamente
  Given usuario con scope ['articles:store'] y permiso articles:store,
        siendo el autor del payload el propio usuario
  When hace POST /articles con título, slug, content y relaciones authors+categories
  Then responde 201 con los atributos enviados
  And el artículo existe en BD con user_id del autor

Scenario: Sin scope → 403
  Given usuario con permiso pero token sin scope articles:store
  Then responde 403  And no se crea nada

Scenario: Sin permiso → 403
  Given usuario con scope articles:store pero sin el permiso Spatie
  Then responde 403  And no se crea nada

Scenario: Crear a nombre de otro usuario → 403 (ownership)
  Given usuario con scope+permiso, pero authors.data.id apunta a OTRO usuario
  Then responde 403  And no se crea nada

Scenario: Sin permiso, 403 aunque falte la relación authors
  Given usuario con scope pero sin permiso, y payload sin authors
  Then responde 403  (la autorización precede a la validación)

Scenario: Rechaza atributos desconocidos
  Given usuario con scope+permiso y un atributo "approved" extra
  Then responde 400  And no se crea nada

Scenario: authors es obligatorio
  Given usuario con scope+permiso y payload sin authors
  Then responde 422  And errors.0.source.pointer == "/data/relationships/authors"

Scenario: categories es obligatorio
  Given usuario con scope+permiso y payload sin categories
  Then responde 422  And errors.0.source.pointer == "/data/relationships/categories"

Scenario Outline: las relaciones deben ser del tipo correcto
  Given usuario con scope+permiso
  When <relationship> se envía con tipo <wrongType>
  Then responde 422  And el pointer apunta a /data/relationships/<relationship>
  Examples:
    | relationship | wrongType  |
    | authors      | categories |
    | categories   | authors    |

Scenario Outline: atributos obligatorios no pueden ir vacíos
  Given usuario con scope+permiso y <field> = ""
  Then responde 422  And pointer == /data/attributes/<field>
  Examples: title | content

Scenario: slug debe ser único
  Given ya existe un artículo con slug "same-slug"
  When se crea otro con el mismo slug
  Then responde 422 con pointer /data/attributes/slug  And sigue habiendo 1 artículo

Scenario Outline: slug inválido se rechaza
  Then responde 422 con pointer /data/attributes/slug
  Examples (slug): "" | "%$%#@" | "with_underscores" | "-start-with-dash" | "end-with-dash-"
```
