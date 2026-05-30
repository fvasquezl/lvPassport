<?php

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Laravel\Passport\Passport;

it('can include comments', function () {
    $article = Article::factory()->create();
    Comment::factory()->count(2)->create(['article_id' => $article->id]);

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->includePaths('comments')
        ->get(route('api.v2.articles.show', $article))
        ->assertOk()
        ->assertJsonCount(2, 'included');
});

it('can fetch related comments', function () {
    $article = Article::factory()->create();
    Comment::factory()->count(2)->create(['article_id' => $article->id]);

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->get(route('api.v2.articles.comments', $article))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('can fetch the comments relationship', function () {
    $article = Article::factory()->create();
    Comment::factory()->count(2)->create(['article_id' => $article->id]);

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->get(route('api.v2.articles.comments.show', $article))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('guests can fetch related comments', function () {
    $article = Article::factory()->create();
    Comment::factory()->count(2)->create(['article_id' => $article->id]);

    $this->jsonApi()
        ->get(route('api.v2.articles.comments', $article))
        ->assertOk();
});
