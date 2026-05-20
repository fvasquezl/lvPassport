<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Autorización: Scopes de Passport y Permissions de Spatie

Este proyecto combina **dos capas de autorización** en cada acción protegida (`store`, `update`, `update-relationship`, …), más una tercera regla de negocio cuando aplica (**ownership**):

1. **Scopes de OAuth 2.0** (Laravel Passport) — vinculados al **token**, no al usuario.
2. **Permissions de Spatie** — vinculadas al **usuario** (o a un rol), persisten en BD.
3. **Ownership** — el recurso debe pertenecer al usuario autenticado.

Las tres se evalúan con AND: si falta cualquiera, el endpoint responde **403 Forbidden**.

### Las dos capas, en breve

|                       | Passport scope                                                     | Spatie permission                                              |
| --------------------- | ------------------------------------------------------------------ | -------------------------------------------------------------- |
| **A qué pertenece**   | Al token de acceso                                                 | Al usuario                                                     |
| **Quién lo asigna**   | El cliente OAuth al pedir el token                                 | El admin (o seeder) al usuario                                 |
| **Dónde vive**        | En el token                                                        | En la BD (`permissions`, `model_has_permissions`)              |
| **Caducidad**         | Vive lo que vive el token                                          | Persiste hasta revocarse                                       |
| **Caso de uso**       | Limitar qué puede hacer **una aplicación cliente** con ese token   | Definir qué puede hacer **el usuario** en el dominio           |
| **Cómo se consulta**  | `$user->tokenCan('articles:update')`                               | `$user->hasPermissionTo('articles:update')`                    |

### Cómo se combinan en código

`app/Policies/ArticlePolicy.php`:

```php
public function update(User $user, Article $article): bool
{
    return $user->tokenCan('articles:update')         // 1) ¿el token tiene este scope?
        && $user->hasPermissionTo('articles:update')  // 2) ¿el usuario tiene esta permission?
        && $article->user->is($user);                 // 3) ¿es el dueño del recurso?
}
```

El `ArticleAuthorizer` (capa JSON:API) delega al policy vía `Gate::inspect('update', $model)`; el `auth:api` middleware en `routes/api.php` se encarga del 401 cuando no hay token.

### Cómo lo prueban los tests

Cada capa se simula con un helper distinto, lo que permite aislarlas:

```php
Passport::actingAs($user, ['articles:update']);   // asigna scopes al token simulado
userWithPermission('articles:update', $user);     // da la permission Spatie al usuario
```

#### `tests/Feature/Articles/UpdateArticlesTest.php`

| Test                                                                | Scope | Permission | Dueño | Status | Qué prueba                                                                |
| ------------------------------------------------------------------- | :---: | :--------: | :---: | :----: | ------------------------------------------------------------------------- |
| `guest users cannot update articles`                                |   —   |     —      |   —   | **401**| Sin token no se llega al policy.                                          |
| `authenticated users can update their articles`                     |  ✅   |     ✅     |  ✅   | **200**| Happy path: los tres checks pasan.                                        |
| `authenticated users cannot update their articles without permissions` | ✅ |     ❌     |  ✅   | **403**| Scope + ownership no bastan: la permission Spatie también es obligatoria. |
| `authenticated users cannot update other articles`                  |  ✅   |     ✅     |  ❌   | **403**| Scope + permission no bastan: el ownership también es obligatorio.        |

#### `tests/Feature/Articles/CreateArticlesTest.php`

Mismo patrón aplicado a `store`:

- `authenticated users can create articles` — scope + permission, y el `authors` del payload apunta al usuario autenticado → **201**.
- `authenticated users cannot create articles without permissions` — scope sin permission → **403**.
- `authenticated users cannot create articles on behalf of other user` — scope + permission, pero `authors` apunta a **otro** usuario → **403** (ownership en `ArticlePolicy::create()`).

### Por qué usar las dos capas

Responden preguntas distintas:

- **El scope responde**: *"¿qué le permite hacer este token específico?"* Un mismo usuario puede tener varios tokens, cada uno con scopes distintos. Una app móvil puede recibir un token sólo con `articles:read`, aunque el usuario sea admin.
- **La permission responde**: *"¿qué puede hacer este usuario en el dominio?"* Es la regla de negocio, independiente del cliente OAuth.

Combinadas dan **defensa en profundidad**:

- Si se compromete un token con scope amplio pero el usuario no tiene la permission → bloqueado.
- Si se revocan las permissions a un usuario, los tokens vivos siguen sin poder hacer nada → bloqueado.
- Si un usuario con permissions amplias usa una app con scopes limitados → la app no puede excederse.

Lo que el usuario puede hacer en una petición es la **intersección** del scope del token, la permission del usuario y, donde aplique, el ownership.

### Notas operativas

- Las permissions se registran en `beforeEach` (`Permission::findOrCreate('articles:update', 'api')`) y el cache de Spatie se limpia con `app(PermissionRegistrar::class)->forgetCachedPermissions()`. El helper `userWithPermission()` (en `tests/Pest.php`) hace `findOrCreate` internamente, así que sólo necesitas registrar a mano las permissions que no pasen por ese helper.
- El guard `api` debe coincidir tanto en Passport como en Spatie. Si registras la permission en otro guard, `hasPermissionTo()` no la encuentra.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
