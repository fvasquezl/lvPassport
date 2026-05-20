<?php

use App\Models\Article;
use App\Models\User;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Permission::findOrCreate('articles:read', 'api');
    Permission::findOrCreate('articles:index', 'api');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('guest users cannot fetch an article',function(){
    $article = Article::factory()->create();

    $this->jsonApi()
        ->get(route('api.v1.articles.show', $article))
        ->assertUnauthorized(); // 401
});

it('authenticated users can fetch an article',function(){
    $article = Article::factory()->create();

    $user = userWithPermission('articles:read');
    Passport::actingAs($user, ['articles:read']);

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

it('authenticated users cannot fetch an article without permission',function(){
    $article = Article::factory()->create();

    Passport::actingAs($article->user, ['articles:read']);

    $this->jsonApi()
        ->get(route('api.v1.articles.show', $article))
        ->assertForbidden(); // 403
});

it('guest users cannot fetch all articles',function(){
   Article::factory()->count(3)->create();

    $this->jsonApi()
        ->get(route('api.v1.articles.index'))
        ->assertUnauthorized(); // 401
});

it('authenticated users cannot fetch all articles without permission',function(){
    Article::factory()->count(3)->create();

    $user = User::factory()->create();
    Passport::actingAs($user, ['articles:index']);

    $this->jsonApi()
        ->get(route('api.v1.articles.index'))
        ->assertForbidden(); // 403
});

it('can fetch all articles', function () {

    $articles = Article::factory()->count(3)->create();

    $user = userWithPermission('articles:index');
    Passport::actingAs($user, ['articles:index']);

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
