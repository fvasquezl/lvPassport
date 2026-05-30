# Authors (`/api/v2/authors`)

> Spec de [`tests/Feature/V2/Authors/ListAuthorsTest.php`](../../../tests/Feature/V2/Authors/ListAuthorsTest.php) · Contexto: [README](../README.md)

Recurso de solo lectura sobre `User`. Lecturas **requieren** scope `read`.

```gherkin
Scenario: Invitado no puede ver un autor                → 401
Scenario: Usuario con read ve un autor                  → 200 (type=authors, name, email, id UUID)
Scenario: Usuario sin scope read no puede ver un autor  → 403
Scenario: Invitado no puede listar autores              → 401
Scenario: Usuario sin scope no puede listar autores     → 403
Scenario: Usuario con read lista autores                → 200 (incluye al usuario actuante)
```
