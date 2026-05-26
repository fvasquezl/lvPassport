<?php

use App\Models\Article;
use App\Models\User;
use Laravel\Passport\Passport;

it('guest users can paginate articles', function () {
    Article::factory()->count(10)->create();

    $this->jsonApi()
        ->page(['size' => 2, 'number' => 1])
        ->get(route('api.v1.articles.index'))
        ->assertOk();
});

it('authenticated users without scope can paginate articles', function () {
    Article::factory()->count(10)->create();

    Passport::actingAs(User::factory()->create());

    $this->jsonApi()
        ->page(['size' => 2, 'number' => 1])
        ->get(route('api.v1.articles.index'))
        ->assertOk();
});

it('can fetch paginate articles', function () {
    Article::factory()->count(10)->create();

    Passport::actingAs(User::factory()->create(), ['articles:index']);

    $response = $this->jsonApi()
        ->page(['size' => 2, 'number' => 3])
        ->get(route('api.v1.articles.index'));

    $response->assertJsonStructure([
        'links' => ['first', 'last', 'prev', 'next'],
    ]);

    $response->assertJsonFragment([
        'first' => route('api.v1.articles.index', ['page[number]' => 1, 'page[size]' => 2]),
        'last' => route('api.v1.articles.index', ['page[number]' => 5, 'page[size]' => 2]),
        'prev' => route('api.v1.articles.index', ['page[number]' => 2, 'page[size]' => 2]),
        'next' => route('api.v1.articles.index', ['page[number]' => 4, 'page[size]' => 2]),
    ]);
});
