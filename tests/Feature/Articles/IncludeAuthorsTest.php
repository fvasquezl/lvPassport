<?php

use App\Models\Article;

it('can include authors', function () {
    $article = Article::factory()->create();

    $this->jsonApi()
        ->includePaths('authors')
        ->get(route('api.v1.articles.show', $article))
        ->assertSee($article->user->name)
        ->assertJsonFragment([
            'related' => route('api.v1.articles.authors', $article),
        ])
        ->assertJsonFragment([
            'self' => route('api.v1.articles.authors.show', $article),
        ]);
});

it('can get the related author', function () {
    $article = Article::factory()->create();
    $this->jsonApi()
        ->get(route('api.v1.articles.authors', $article))
        ->assertSee($article->user->name);
});
