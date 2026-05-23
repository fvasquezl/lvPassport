<?php

use App\Models\User;
use Laravel\Passport\Passport;

/**
 * http://localhost/api/v1/authors?include=articles
 * http://localhost/api/v1/authors/{author}?include=articles
 */
it('can include articles', function () {
    $author = User::factory()->hasArticles()->create();

    $user = User::factory()->create();
    Passport::actingAs($user, ['authors:show']);

    $this->jsonApi()
        ->includePaths('articles')
        ->get(route('api.v1.authors.show', $author))
        ->assertJsonFragment(['title' => $author->articles[0]->title])
        ->assertJsonFragment([
            'related' => route('api.v1.authors.articles', $author),
        ])
        ->assertJsonFragment([
            'self' => route('api.v1.authors.articles.show', $author),
        ]);
});

it('can fetch related articles', function () {
    $author = User::factory()->hasArticles()->create();

    $user = User::factory()->create();
    Passport::actingAs($user, ['authors:show-articles']);

    $this->jsonApi()
        ->get(route('api.v1.authors.articles', $author))
        ->assertJsonFragment(['title' => $author->articles[0]->title]);
});

it('can fetch articles relationship', function () {
    $author = User::factory()->hasArticles()->create();

    $user = User::factory()->create();
    Passport::actingAs($user, ['authors:show-articles']);

    $this->jsonApi()
        ->get(route('api.v1.authors.articles.show', $author))
        ->assertJsonFragment(['id' => (string) $author->articles[0]->getRouteKey()]);
});

it('guest users cannot fetch related articles', function () {
    $author = User::factory()->hasArticles()->create();

    $this->jsonApi()
        ->get(route('api.v1.authors.articles', $author))
        ->assertUnauthorized(); // 401
});

it('authenticated users without scope cannot fetch related articles', function () {
    $author = User::factory()->hasArticles()->create();

    Passport::actingAs(User::factory()->create());

    $this->jsonApi()
        ->get(route('api.v1.authors.articles', $author))
        ->assertForbidden(); // 403
});
