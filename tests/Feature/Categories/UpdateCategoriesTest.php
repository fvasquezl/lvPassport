<?php

use App\Models\Category;
use Laravel\Passport\Passport;

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
});

it('authenticated users can update their categories', function () {
    $category = Category::factory()->create();

    Passport::actingAs(userWithPermission('categories:update'));

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
        ->assertOK(); // 200

    $this->assertDatabaseHas('categories', [
        'name' => 'Name changed',
        'slug' => 'slug-changed',
    ]);
});

it('authenticated users can update single attribute', function (array $attributes) {
    $category = Category::factory()->create();

    Passport::actingAs(userWithPermission('categories:update'));

    $this->jsonApi()
        ->withData([
            'type' => 'categories',
            'id' => $category->getRouteKey(),
            'attributes' => $attributes,
        ])
        ->patch(route('api.v1.categories.update', $category))
        ->assertOk();

    $this->assertDatabaseHas('categories', $attributes);
})
    ->with([
        'name only' => [['name' => 'Name changed']],
        'slug only' => [['slug' => 'slug-changed']],
    ]);
