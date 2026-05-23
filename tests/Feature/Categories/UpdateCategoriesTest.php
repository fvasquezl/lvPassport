<?php

use App\Models\Category;
use App\Models\User;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Permission::findOrCreate('categories:update', 'api');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('guest users cannot update categories', function () {
    $category = Category::factory()->create();

    $this->jsonApi()
        ->withData([
            'type' => 'categories',
            'id' => $category->getRouteKey(),
            'attributes' => [
                'name' => 'Name changed',
                'slug' => 'slug-changed',
            ],
        ])
        ->patch(route('api.v1.categories.update', $category))
        ->assertUnauthorized(); // 401
    $this->assertDatabaseHas('categories', ['id' => $category->id,
        'name' => $category->name,
        'slug' => $category->slug,
    ]);
});

it('authenticated users can update categories', function () {
    $category = Category::factory()->create();

    $user = userWithPermission('categories:update');
    Passport::actingAs($user, ['categories:update']);

    $this->jsonApi()
        ->withData([
            'type' => 'categories',
            'id' => $category->getRouteKey(),
            'attributes' => [
                'name' => 'Name changed',
                'slug' => 'slug-changed',
            ],
        ])
        ->patch(route('api.v1.categories.update', $category))
        ->assertOk(); // 200

    $this->assertDatabaseHas('categories', [
        'name' => 'Name changed',
        'slug' => 'slug-changed',
    ]);
});

it('authenticated users can update single attribute', function (array $attributes) {
    $category = Category::factory()->create();

    $user = userWithPermission('categories:update');
    Passport::actingAs($user, ['categories:update']);

    $this->jsonApi()
        ->withData([
            'type' => 'categories',
            'id' => $category->getRouteKey(),
            'attributes' => $attributes,
        ])
        ->patch(route('api.v1.categories.update', $category))
        ->assertOk();

    $this->assertDatabaseHas('categories', $attributes + $category->only(['name', 'slug']) + ['id' => $category->id]);
})
    ->with([
        'name only' => [['name' => 'Name changed']],
        'slug only' => [['slug' => 'slug-changed']],
    ]);

it('authenticated users without scope cannot update categories', function () {
    $category = Category::factory()->create();

    $user = userWithPermission('categories:update');
    Passport::actingAs($user);

    $this->jsonApi()
        ->withData([
            'type' => 'categories',
            'id' => $category->getRouteKey(),
            'attributes' => [
                'name' => 'Name changed',
                'slug' => 'slug-changed',
            ],
        ])
        ->patch(route('api.v1.categories.update', $category))
        ->assertForbidden(); // 403

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => $category->name,
        'slug' => $category->slug,
    ]);
});

it('authenticated users without permission cannot update categories', function () {
    $category = Category::factory()->create();

    $user = User::factory()->create();
    Passport::actingAs($user, ['categories:update']);

    $this->jsonApi()
        ->withData([
            'type' => 'categories',
            'id' => $category->getRouteKey(),
            'attributes' => [
                'name' => 'Name changed',
                'slug' => 'slug-changed',
            ],
        ])
        ->patch(route('api.v1.categories.update', $category))
        ->assertForbidden(); // 403

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => $category->name,
        'slug' => $category->slug,
    ]);
});

it('slug must be unique on update', function () {
    Category::factory()->create(['slug' => 'taken-slug']);
    $category = Category::factory()->create();

    $user = userWithPermission('categories:update');
    Passport::actingAs($user, ['categories:update']);

    $this->jsonApi()
        ->withData([
            'type' => 'categories',
            'id' => $category->getRouteKey(),
            'attributes' => [
                'slug' => 'taken-slug',
            ],
        ])
        ->patch(route('api.v1.categories.update', $category))
        ->assertUnprocessable() // 422
        ->assertSee('data\/attributes\/slug');

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'slug' => $category->slug,
    ]);
});

it('slug must only contain letters numbers and dashes on update', function () {
    $category = Category::factory()->create();

    $user = userWithPermission('categories:update');
    Passport::actingAs($user, ['categories:update']);

    $this->jsonApi()
        ->withData([
            'type' => 'categories',
            'id' => $category->getRouteKey(),
            'attributes' => [
                'slug' => '%$%#@',
            ],
        ])
        ->patch(route('api.v1.categories.update', $category))
        ->assertUnprocessable() // 422
        ->assertSee('data\/attributes\/slug');

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'slug' => $category->slug,
    ]);
});
