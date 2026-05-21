<?php

use App\Models\Article;
use App\Models\User;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * http://localhost/api/v1/articles?include=categories
 * http://localhost/api/v1/articles/category-slug?include=categories
 */


it('can include categories', function () {

    $article = Article::factory()->create();

    $user = User::factory()->create();
    Passport::actingAs($user,['articles:show']);

    $this->jsonApi()
        ->includePaths('categories')
        ->get(route('api.v1.articles.show', $article))
        ->assertSee($article->category->name)
        ->assertJsonFragment([
            'related' => route('api.v1.articles.categories', $article),
        ])
        ->assertJsonFragment([
            'self' => route('api.v1.articles.categories.show', $article),
        ]);
});

it('can fetch related categories', function () {

    $article = Article::factory()->create();

    $this->jsonApi()
        ->get(route('api.v1.articles.categories', $article))
        ->assertSee($article->category->name);

    $this->jsonApi()
        ->get(route('api.v1.articles.categories.show', $article))
        ->assertSee($article->category->getRouteKey());

});
