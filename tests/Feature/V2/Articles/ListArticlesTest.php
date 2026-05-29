<?php

use App\Models\Article;
use App\Models\User;
use Laravel\Passport\Passport;

it('guest users can fetch an article', function () {
    $article = Article::factory()->create();

    $this->jsonApi()
        ->get(route('api.v2.articles.show', $article))
        ->assertOk();
});

it('authenticated users can fetch an article', function () {
    $article = Article::factory()->create();

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()->get(route('api.v2.articles.show', $article))
        ->assertOk()
        ->assertJson([
            'data' => [
                'type' => 'articles',
                'id' => (string) $article->getRouteKey(),
                'attributes' => [
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'content' => $article->content,
                    'createdAt' => $article->created_at->toJSON(),
                    'updatedAt' => $article->updated_at->toJSON(),
                ],
                'links' => [
                    'self' => route('api.v2.articles.show', $article),
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
            'links' => [
                'self' => route('api.v2.articles.show', $article),
            ],
        ]);
});

it('authenticated users can fetch an article without scope', function () {
    $article = Article::factory()->create();

    Passport::actingAs(User::factory()->create());

    $this->jsonApi()
        ->get(route('api.v2.articles.show', $article))
        ->assertOk();
});

it('guest users can fetch all articles', function () {
    Article::factory()->count(3)->create();

    $this->jsonApi()
        ->get(route('api.v2.articles.index'))
        ->assertOk();
});

it('authenticated users can fetch all articles without scope', function () {
    Article::factory()->count(3)->create();

    Passport::actingAs(User::factory()->create());

    $this->jsonApi()
        ->get(route('api.v2.articles.index'))
        ->assertOk();
});

it('can fetch all articles', function () {
    $articles = Article::factory()->count(3)->create();

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()->get(route('api.v2.articles.index'))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJson([
            'data' => $articles->map(fn (Article $article) => [
                'type' => 'articles',
                'id' => (string) $article->getRouteKey(),
                'attributes' => [
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'content' => $article->content,
                    'createdAt' => $article->created_at->toJSON(),
                    'updatedAt' => $article->updated_at->toJSON(),
                ],
                'links' => [
                    'self' => route('api.v2.articles.show', $article),
                ],
            ])->all(),
        ]);
});
