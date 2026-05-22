<?php

use App\Models\Article;
use App\Models\User;
use Laravel\Passport\Passport;

it('can include authors', function () {
    $article = Article::factory()->create();

    $user = User::factory()->create();
    Passport::actingAs($user, ['articles:show']);

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

    $user = User::factory()->create();
    Passport::actingAs($user, ['articles:show-authors']);

    $this->jsonApi()
        ->get(route('api.v1.articles.authors', $article))
        ->assertSee($article->user->name);
});
