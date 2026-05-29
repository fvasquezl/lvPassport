<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Laravel\Passport\Passport;

it('guest users can filter articles', function () {
    Article::factory()->create();

    $this->jsonApi()
        ->filter(['title' => 'whatever'])
        ->get(route('api.v2.articles.index'))
        ->assertOk();
});

it('authenticated users without scope can filter articles', function () {
    Article::factory()->create();

    Passport::actingAs(User::factory()->create());

    $this->jsonApi()
        ->filter(['title' => 'whatever'])
        ->get(route('api.v2.articles.index'))
        ->assertOk();
});

it('can filter articles by title', function () {
    Article::factory()->create(['title' => 'Aprende laravel desde cero']);
    Article::factory()->create(['title' => 'Other Article']);

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->filter(['title' => 'Laravel'])
        ->get(route('api.v2.articles.index'))
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['title' => 'Aprende laravel desde cero'])
        ->assertJsonMissing(['title' => 'Other Article']);
});

it('can filter articles by content', function () {
    Article::factory()->create(['content' => '<div>Aprende laravel desde cero</div>']);
    Article::factory()->create(['content' => '<div>Other Article</div>']);

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->filter(['content' => 'Laravel'])
        ->get(route('api.v2.articles.index'))
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['content' => '<div>Aprende laravel desde cero</div>'])
        ->assertJsonMissing(['content' => '<div>Other Article</div>']);
});

it('can filter articles by year', function () {
    Article::factory()->create([
        'title' => 'Article from 2020',
        'created_at' => now()->year(2020),
    ]);
    Article::factory()->create([
        'title' => 'Article from 2021',
        'created_at' => now()->year(2021),
    ]);

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->filter(['year' => 2020])
        ->get(route('api.v2.articles.index'))
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['title' => 'Article from 2020'])
        ->assertJsonMissing(['title' => 'Article from 2021']);
});

it('can filter articles by month', function () {
    Article::factory()->create([
        'title' => 'Article from February',
        'created_at' => now()->startOfMonth()->setMonth(2),
    ]);
    Article::factory()->create([
        'title' => 'Another Article from February',
        'created_at' => now()->startOfMonth()->setMonth(2),
    ]);
    Article::factory()->create([
        'title' => 'Article from January',
        'created_at' => now()->startOfMonth()->setMonth(1),
    ]);

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->filter(['month' => 2])
        ->get(route('api.v2.articles.index'))
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['title' => 'Article from February'])
        ->assertJsonFragment(['title' => 'Another Article from February'])
        ->assertJsonMissing(['title' => 'Article from January']);
});

it('cannot filter articles by unknown filters', function () {
    Article::factory()->create();

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->filter(['unknown' => 2])
        ->get(route('api.v2.articles.index'))
        ->assertBadRequest();
});

it('can search articles by title and content', function () {
    Article::factory()->create([
        'title' => 'Article from Aprendible',
        'content' => 'Content',
    ]);
    Article::factory()->create([
        'title' => 'Another Article',
        'content' => 'Content Aprendible...',
    ]);
    Article::factory()->create([
        'title' => 'Title 2',
        'content' => 'Content 2',
    ]);

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->filter(['search' => 'Aprendible'])
        ->get(route('api.v2.articles.index'))
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['title' => 'Article from Aprendible'])
        ->assertJsonFragment(['title' => 'Another Article'])
        ->assertJsonMissing(['title' => 'Title 2']);
});

it('can search articles by title and content with multiple terms', function () {
    Article::factory()->create([
        'title' => 'Article from Aprendible',
        'content' => 'Content',
    ]);
    Article::factory()->create([
        'title' => 'Another Article',
        'content' => 'Content Aprendible..',
    ]);
    Article::factory()->create([
        'title' => 'Another Laravel Article',
        'content' => 'Content...',
    ]);
    Article::factory()->create([
        'title' => 'Title 2',
        'content' => 'Content 2',
    ]);

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->filter(['search' => 'Aprendible Laravel'])
        ->get(route('api.v2.articles.index'))
        ->assertJsonCount(3, 'data')
        ->assertJsonFragment(['title' => 'Article from Aprendible'])
        ->assertJsonFragment(['title' => 'Another Article'])
        ->assertJsonFragment(['title' => 'Another Laravel Article'])
        ->assertJsonMissing(['title' => 'Title 2']);
});

it('can filter articles by categories', function () {
    Article::factory()->count(2)->create();

    $category = Category::factory()->hasArticles(2)->create();

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->filter(['categories' => $category->getRouteKey()])
        ->get(route('api.v2.articles.index'))
        ->assertJsonCount(2, 'data');
});

it('can filter articles by multiple categories', function () {
    Article::factory()->count(2)->create();

    $category = Category::factory()->hasArticles(2)->create();
    $category2 = Category::factory()->hasArticles(3)->create();

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->filter([
            'categories' => $category->getRouteKey().','.$category2->getRouteKey(),
        ])
        ->get(route('api.v2.articles.index'))
        ->assertJsonCount(5, 'data');
});

it('can filter articles by authors', function () {
    $author = User::factory()->hasArticles(2)->create();

    Article::factory()->count(2)->create();

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->filter(['authors' => $author->name])
        ->get(route('api.v2.articles.index'))
        ->assertJsonCount(2, 'data');
});

it('can filter articles by multiple authors', function () {
    $author = User::factory()->hasArticles(2)->create();
    $author2 = User::factory()->hasArticles(3)->create();

    Article::factory()->count(2)->create();

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()
        ->filter([
            'authors' => $author->name.','.$author2->name,
        ])
        ->get(route('api.v2.articles.index'))
        ->assertJsonCount(5, 'data');
});
