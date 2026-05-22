<?php

use App\Models\Article;
use App\Models\User;
use Laravel\Passport\Passport;

it('can sort articles by title asc', function () {

    Article::factory()->create(['title' => 'C title']);
    Article::factory()->create(['title' => 'A title']);
    Article::factory()->create(['title' => 'B title']);

    $user = User::factory()->create();
    Passport::actingAs($user, ['articles:index']);

    $url = route('api.v1.articles.index', ['sort' => 'title']);

    $this->jsonApi()->get($url)->assertSeeInOrder([
        'A title',
        'B title',
        'C title',
    ]);
});

it('can sort articles by title desc', function () {

    Article::factory()->create(['title' => 'C title']);
    Article::factory()->create(['title' => 'A title']);
    Article::factory()->create(['title' => 'B title']);

    $user = User::factory()->create();
    Passport::actingAs($user, ['articles:index']);

    $url = route('api.v1.articles.index', ['sort' => '-title']);

    $this->jsonApi()->get($url)->assertSeeInOrder([
        'C title',
        'B title',
        'A title',
    ]);
});


it('cant sort articles by unknown fields', function () {

    Article::factory()->times(3)->create();

    $user = User::factory()->create();
    Passport::actingAs($user, ['articles:index']);

    $url = route('api.v1.articles.index').'?sort=unknown';

    $this->jsonApi()->get($url)->assertBadRequest();
});
