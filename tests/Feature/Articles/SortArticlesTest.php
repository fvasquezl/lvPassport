<?php

use App\Models\Article;
use App\Models\User;
use Laravel\Passport\Passport;

it('guest users can sort articles', function () {
    Article::factory()->count(3)->create();

    $this->jsonApi()
        ->sort('title')
        ->get(route('api.v1.articles.index'))
        ->assertOk();
});

it('authenticated users without scope can sort articles', function () {
    Article::factory()->count(3)->create();

    Passport::actingAs(User::factory()->create());

    $this->jsonApi()
        ->sort('title')
        ->get(route('api.v1.articles.index'))
        ->assertOk();
});

it('can sort articles by title', function (string $sort, array $expected) {
    Article::factory()->create(['title' => 'C title']);
    Article::factory()->create(['title' => 'A title']);
    Article::factory()->create(['title' => 'B title']);

    Passport::actingAs(User::factory()->create(), ['articles:index']);

    $this->jsonApi()
        ->sort($sort)
        ->get(route('api.v1.articles.index'))
        ->assertSeeInOrder($expected);
})
    ->with([
        'asc' => ['title', ['A title', 'B title', 'C title']],
        'desc' => ['-title', ['C title', 'B title', 'A title']],
    ]);

it('cannot sort articles by unknown fields', function () {
    Article::factory()->count(3)->create();

    Passport::actingAs(User::factory()->create(), ['articles:index']);

    $this->jsonApi()
        ->sort('unknown')
        ->get(route('api.v1.articles.index'))
        ->assertBadRequest();
});
