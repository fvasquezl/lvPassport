<?php

use App\Models\Article;

it('can fetch a single article', function () {
    $article = Article::factory()->create();

    $this->jsonApi()->get(route('api.v1.articles.show', $article))
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
                    'self' => route('api.v1.articles.show', $article),
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
            'links' => [
                'self' => route('api.v1.articles.show', $article),
            ],
        ]);
});

it('can fetch all articles', function () {

    $articles = Article::factory()->count(3)->create();

    $this->jsonApi()->get(route('api.v1.articles.index'))
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
                    'self' => route('api.v1.articles.show', $article),
                ],
            ])->all(),
        ]);
});
