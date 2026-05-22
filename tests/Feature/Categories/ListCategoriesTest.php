<?php

use App\Models\Category;

it(description: 'can fetch a single category', closure: function () {
    $category = Category::factory()->create();

    $this->jsonApi()->get(route('api.v1.categories.show', $category))
        ->assertOk()
        ->assertJson([
            'data' => [
                'type' => 'categories',
                'id' => (string) $category->getRouteKey(),
                'attributes' => [
                    'name' => $category->name,
                    'slug' => $category->slug,
                ],
                'links' => [
                    'self' => route('api.v1.categories.show', $category),
                ],
            ],
        ]);
});

it(description: 'can fetch all categories', closure: function () {
    $categories = Category::factory()->count(3)->create();

    $this->jsonApi()->get(route('api.v1.categories.index'))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJson([
            'data' => $categories->map(fn ($category) => [
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
