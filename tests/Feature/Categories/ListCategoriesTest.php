<?php

use App\Models\Category;
use App\Models\User;
use Laravel\Passport\Passport;

it('guest users can fetch a single category', function () {
    $category = Category::factory()->create();

    $this->jsonApi()
        ->get(route('api.v1.categories.show', $category))
        ->assertOk();
});

it('authenticated users can fetch a single category', function () {
    $category = Category::factory()->create();

    $user = User::factory()->create();
    Passport::actingAs($user, ['categories:show']);

    $this->jsonApi()->get(route('api.v1.categories.show', $category))
        ->assertOk()
        ->assertJson([
            'data' => [
                'type' => 'categories',
                'id' => (string) $category->getRouteKey(),
                'attributes' => [
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'createdAt' => $category->created_at->toJSON(),
                    'updatedAt' => $category->updated_at->toJSON(),
                ],
                'links' => [
                    'self' => route('api.v1.categories.show', $category),
                ],
            ],
        ]);
});

it('authenticated users can fetch a single category without scope', function () {
    $category = Category::factory()->create();

    $user = User::factory()->create();
    Passport::actingAs($user);

    $this->jsonApi()
        ->get(route('api.v1.categories.show', $category))
        ->assertOk();
});

it('guest users can fetch all categories', function () {
    Category::factory()->count(3)->create();

    $this->jsonApi()
        ->get(route('api.v1.categories.index'))
        ->assertOk();
});

it('authenticated users can fetch all categories without scope', function () {
    Category::factory()->count(3)->create();

    $user = User::factory()->create();
    Passport::actingAs($user);

    $this->jsonApi()
        ->get(route('api.v1.categories.index'))
        ->assertOk();
});

it('can fetch all categories', function () {
    $categories = Category::factory()->count(3)->create();

    $user = User::factory()->create();
    Passport::actingAs($user, ['categories:index']);

    $this->jsonApi()->get(route('api.v1.categories.index'))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJson([
            'data' => $categories->map(fn (Category $category) => [
                'type' => 'categories',
                'id' => (string) $category->getRouteKey(),
                'attributes' => [
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'createdAt' => $category->created_at->toJSON(),
                    'updatedAt' => $category->updated_at->toJSON(),
                ],
                'links' => [
                    'self' => route('api.v1.categories.show', $category),
                ],
            ])->all(),
        ]);
});
