<?php

use App\Models\Category;
use App\Models\User;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Permission::findOrCreate('categories:store', 'api');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('guest users cannot create categories', function () {
    $data = jsonData(Category::factory()->make());

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertUnauthorized();

    $this->assertDatabaseEmpty('categories');
});

it('authenticated users without scope cannot create categories', function () {
    $data = jsonData(Category::factory()->make());

    Passport::actingAs(userWithPermission('categories:store'));

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertForbidden();

    $this->assertDatabaseEmpty('categories');
});

it('authenticated users without permission cannot create categories', function () {
    $data = jsonData(Category::factory()->make());

    Passport::actingAs(User::factory()->create(), ['categories:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertForbidden();

    $this->assertDatabaseEmpty('categories');
});

it('users with permission can create categories', function () {
    $data = jsonData(
        $category = Category::factory()->make()
    );

    $user = userWithPermission('categories:store');
    Passport::actingAs($user, ['categories:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertCreated();

    $this->assertDatabaseHas('categories', [
        $category->getRouteKeyName() => $category->getRouteKey(),
    ]);
});

it('category name is required', function () {
    $data = jsonData(Category::factory()->make(['name' => '']));

    $user = userWithPermission('categories:store');
    Passport::actingAs($user, ['categories:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.source.pointer', '/data/attributes/name');

    $this->assertDatabaseEmpty('categories');
});

it('slug is required', function () {
    $data = jsonData(Category::factory()->make(['slug' => '']));

    $user = userWithPermission('categories:store');
    Passport::actingAs($user, ['categories:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.source.pointer', '/data/attributes/slug');

    $this->assertDatabaseEmpty('categories');
});

it('slug must be unique', function () {
    Category::factory()->create(['slug' => 'same-slug']);

    $data = jsonData(Category::factory()->make(['slug' => 'same-slug']));

    $user = userWithPermission('categories:store');
    Passport::actingAs($user, ['categories:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.source.pointer', '/data/attributes/slug');

    $this->assertDatabaseCount('categories', 1);
});

it('rejects invalid slug formats', function (string $slug) {
    $data = jsonData(Category::factory()->make(['slug' => $slug]));

    $user = userWithPermission('categories:store');
    Passport::actingAs($user, ['categories:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.source.pointer', '/data/attributes/slug');

    $this->assertDatabaseEmpty('categories');
})
    ->with([
        'special chars' => '%$%#@',
        'underscores' => 'with_underscores',
        'starts with dash' => '-start-with-dash',
        'ends with dash' => 'end-with-dash-',
    ]);
