<?php

use App\Models\Article;
use App\Models\User;
use Laravel\Passport\Passport;

it('can include categories', function () {
    $article = Article::factory()->create();

    Passport::actingAs(User::factory()->create(), ['articles:show']);

    $this->jsonApi()
        ->includePaths('categories')
        ->get(route('api.v1.articles.show', $article))
        ->assertJsonFragment(['name' => $article->category->name])
        ->assertJsonFragment([
            'related' => route('api.v1.articles.categories', $article),
        ])
        ->assertJsonFragment([
            'self' => route('api.v1.articles.categories.show', $article),
        ]);
});

it('can fetch related category', function () {
    $article = Article::factory()->create();

    Passport::actingAs(User::factory()->create(), ['articles:show-categories']);

    $this->jsonApi()
        ->get(route('api.v1.articles.categories', $article))
        ->assertJsonFragment(['name' => $article->category->name]);
});

it('can fetch category relationship', function () {
    $article = Article::factory()->create();

    Passport::actingAs(User::factory()->create(), ['articles:show-categories']);

    $this->jsonApi()
        ->get(route('api.v1.articles.categories.show', $article))
        ->assertJsonFragment(['id' => (string) $article->category->getRouteKey()]);
});

it('guest users cannot fetch related category', function () {
    $article = Article::factory()->create();

    $this->jsonApi()
        ->get(route('api.v1.articles.categories', $article))
        ->assertUnauthorized();
});

it('authenticated users without scope cannot fetch related category', function () {
    $article = Article::factory()->create();

    Passport::actingAs(User::factory()->create());

    $this->jsonApi()
        ->get(route('api.v1.articles.categories', $article))
        ->assertForbidden();
});
