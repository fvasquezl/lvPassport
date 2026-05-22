<?php

use App\Models\Category;

/**
 * http://localhost/api/v1/categories?include=articles
 * http://localhost/api/v1/categories/category-slug?include=articles
 */
it('can include articles', function () {

    $category = Category::factory()->hasArticles()->create();

    $this->jsonApi()
        ->includePaths('articles')
        ->get(route('api.v1.categories.show', $category))
        ->assertSee($category->articles[0]->title)
        ->assertJsonFragment([
            'related' => route('api.v1.categories.articles', $category),
        ])
        ->assertJsonFragment([
            'self' => route('api.v1.categories.articles.show', $category),
        ]);
});

it('can fetch related articles', function () {

    $category = Category::factory()->hasArticles()->create();

    $this->jsonApi()
        ->get(route('api.v1.categories.articles', $category))
        ->assertSee($category->articles[0]->title);

    $this->jsonApi()
        ->get(route('api.v1.categories.articles.show', $category))
        ->assertSee($category->articles[0]->getRouteKey());

});
