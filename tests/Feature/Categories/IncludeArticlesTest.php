<?php

use App\Models\Category;
use App\Models\User;
use Laravel\Passport\Passport;

/**
 * http://localhost/api/v1/categories?include=articles
 * http://localhost/api/v1/categories/category-slug?include=articles
 */
it('guest users can fetch related articles', function () {

    $category = Category::factory()->hasArticles()->create();

    $this->jsonApi()
        ->get(route('api.v1.categories.articles', $category))
        ->assertOk();
});

it('authenticated users without scope can fetch related articles', function () {

    $category = Category::factory()->hasArticles()->create();

    Passport::actingAs(User::factory()->create());

    $this->jsonApi()
        ->get(route('api.v1.categories.articles', $category))
        ->assertOk();
});

it('can include articles', function () {

    $category = Category::factory()->hasArticles()->create();

    $user = User::factory()->create();
    Passport::actingAs($user, ['categories:show']);

    $this->jsonApi()
        ->includePaths('articles')
        ->get(route('api.v1.categories.show', $category))
        ->assertJsonFragment(['title' => $category->articles[0]->title])
        ->assertJsonFragment([
            'related' => route('api.v1.categories.articles', $category),
        ])
        ->assertJsonFragment([
            'self' => route('api.v1.categories.articles.show', $category),
        ]);
});

it('can fetch related articles', function () {

    $category = Category::factory()->hasArticles()->create();

    $user = User::factory()->create();
    Passport::actingAs($user, ['categories:show-articles']);

    $this->jsonApi()
        ->get(route('api.v1.categories.articles', $category))
        ->assertJsonFragment(['title' => $category->articles[0]->title]);
});

it('can fetch articles relationship', function () {

    $category = Category::factory()->hasArticles()->create();

    $user = User::factory()->create();
    Passport::actingAs($user, ['categories:show-articles']);

    $this->jsonApi()
        ->get(route('api.v1.categories.articles.show', $category))
        ->assertJsonFragment(['id' => (string) $category->articles[0]->getRouteKey()]);
});
