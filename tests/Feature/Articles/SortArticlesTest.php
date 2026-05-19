<?php

use App\Models\Article;

it('can sort articles by title asc', function () {

    Article::factory()->create(['title' => 'C title']);
    Article::factory()->create(['title' => 'A title']);
    Article::factory()->create(['title' => 'B title']);

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

    $url = route('api.v1.articles.index', ['sort' => '-title']);

    $this->jsonApi()->get($url)->assertSeeInOrder([
        'C title',
        'B title',
        'A title',
    ]);
});

it('can sort articles by title and content', function () {

    Article::factory()->create([
        'title' => 'C title',
        'content' => 'B content',
    ]);
    Article::factory()->create([
        'title' => 'A title',
        'content' => 'C content',
    ]);
    Article::factory()->create([
        'title' => 'B title',
        'content' => 'D content',
    ]);

    $url = route('api.v1.articles.index').'?sort=title,-content';

    $this->jsonApi()->get($url)->assertSeeInOrder([
        'A title',
        'B title',
        'C title',
    ]);

    $url = route('api.v1.articles.index').'?sort=-content,title';

    $this->jsonApi()->get($url)->assertSeeInOrder([
        'D content',
        'C content',
        'B content',
    ]);
});

it('can sort articles by unknown fields', function () {

    Article::factory()->times(3)->create();

    $url = route('api.v1.articles.index').'?sort=unknown';

    $this->jsonApi()->get($url)->assertBadRequest();
});
