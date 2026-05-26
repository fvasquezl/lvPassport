<?php

use App\Models\Article;
use App\Models\User;
use Laravel\Passport\Passport;

it('can include authors', function () {
    $article = Article::factory()->create();

    Passport::actingAs(User::factory()->create(), ['articles:show']);

    $this->jsonApi()
        ->includePaths('authors')
        ->get(route('api.v1.articles.show', $article))
        ->assertJsonFragment(['name' => $article->user->name])
        ->assertJsonFragment([
            'related' => route('api.v1.articles.authors', $article),
        ])
        ->assertJsonFragment([
            'self' => route('api.v1.articles.authors.show', $article),
        ]);
});

it('can fetch related author', function () {
    $article = Article::factory()->create();

    Passport::actingAs(User::factory()->create(), ['articles:show-authors']);

    $this->jsonApi()
        ->get(route('api.v1.articles.authors', $article))
        ->assertJsonFragment(['name' => $article->user->name]);
});

it('can fetch author relationship', function () {
    $article = Article::factory()->create();

    Passport::actingAs(User::factory()->create(), ['articles:show-authors']);

    $this->jsonApi()
        ->get(route('api.v1.articles.authors.show', $article))
        ->assertJsonFragment(['id' => $article->user->getRouteKey()]);
});

it('guest users can fetch related author', function () {
    $article = Article::factory()->create();

    $this->jsonApi()
        ->get(route('api.v1.articles.authors', $article))
        ->assertOk();
});

it('authenticated users without scope can fetch related author', function () {
    $article = Article::factory()->create();

    Passport::actingAs(User::factory()->create());

    $this->jsonApi()
        ->get(route('api.v1.articles.authors', $article))
        ->assertOk();
});
