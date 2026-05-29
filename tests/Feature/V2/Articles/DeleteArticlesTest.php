<?php

use App\Models\Article;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Permission::findOrCreate('articles:delete', 'api');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('guest users cannot delete articles', function () {
    $article = Article::factory()->create();

    $this->jsonApi()
        ->delete(route('api.v2.articles.destroy', $article))
        ->assertUnauthorized();

    $this->assertDatabaseHas('articles', ['id' => $article->id]);
});

it('authenticated users can delete their articles', function () {
    $article = Article::factory()->create();

    $user = userWithPermission('articles:delete', $article->user);
    Passport::actingAs($user, ['articles:delete']);

    $this->jsonApi()
        ->delete(route('api.v2.articles.destroy', $article))
        ->assertNoContent();

    $this->assertDatabaseEmpty('articles');
});

it('authenticated users without scope cannot delete articles', function () {
    $article = Article::factory()->create();

    $user = userWithPermission('articles:delete', $article->user);
    Passport::actingAs($user);

    $this->jsonApi()
        ->delete(route('api.v2.articles.destroy', $article))
        ->assertForbidden();

    $this->assertDatabaseHas('articles', ['id' => $article->id]);
});

it('authenticated users cannot delete their articles without permissions', function () {
    $article = Article::factory()->create();

    Passport::actingAs($article->user, ['articles:delete']);

    $this->jsonApi()
        ->delete(route('api.v2.articles.destroy', $article))
        ->assertForbidden();

    $this->assertDatabaseHas('articles', ['id' => $article->id]);
});

it('authenticated users cannot delete other articles', function () {
    $article = Article::factory()->create();

    $user = userWithPermission('articles:delete');
    Passport::actingAs($user, ['articles:delete']);

    $this->jsonApi()
        ->delete(route('api.v2.articles.destroy', $article))
        ->assertForbidden();

    $this->assertDatabaseHas('articles', ['id' => $article->id]);
});
