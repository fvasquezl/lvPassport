<?php

use App\Models\Article;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Permission::findOrCreate('articles:delete', 'web');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('guest users cannot delete articles', function () {

    $article = Article::factory()->create();

    $this->jsonApi()
        ->delete(route('api.v1.articles.destroy', $article))
        ->assertUnauthorized(); // 401
});

it('authenticated users can delete their articles', function () {

    $article = Article::factory()->create();

    Sanctum::actingAs(
        userWithPermission('articles:delete', $article->user),
    );

    $this->jsonApi()
        ->delete(route('api.v1.articles.destroy', $article))
        ->assertNoContent(); // 204

});

it('authenticated users cannot delete their articles without permissions', function () {

    $article = Article::factory()->create();

    Sanctum::actingAs($article->user);

    $this->jsonApi()
        ->delete(route('api.v1.articles.destroy', $article))
        ->assertForbidden(); // 403

});

it('authenticated users cannot delete other articles', function () {

    $article = Article::factory()->create();

    Sanctum::actingAs(User::factory()->create());

    $this->jsonApi()
        ->delete(route('api.v1.articles.destroy', $article))
        ->assertForbidden(); // 403

});
