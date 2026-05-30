# Autorización de autores (`AuthorPolicy`)

> Spec de [`tests/Feature/V2/Auth/AuthorPolicyTest.php`](../../../tests/Feature/V2/Auth/AuthorPolicyTest.php) · Contexto: [README](../README.md)

> Decisión de diseño V2: las lecturas de autores usan `tokenCan('read')` **únicamente** (sin
> permission Spatie). Toda escritura de autores está prohibida vía API.

```gherkin
Scenario: viewAny permitido con scope read
  Given un usuario con token de scope ['read']
  Then Gate::allows('viewAny', User::class) es true

Scenario: viewAny denegado sin scope read
  Given un usuario con token sin scopes
  Then AuthorPolicy::viewAny es false

Scenario: view permitido con scope read
  Given un usuario con token ['read'] y un autor objetivo
  Then Gate::allows('view', $author) es true

Scenario: view denegado sin scope read
  Then AuthorPolicy::view es false

Scenario Outline: las escrituras de autores siempre se deniegan
  Then AuthorPolicy::<action> es false
  Examples: create | update | delete
```
