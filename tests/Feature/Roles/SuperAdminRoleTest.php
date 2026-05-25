<?php

use App\Models\Article;
use App\Models\Category;
use Laravel\Passport\Passport;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('a super-admin can create articles without permissions or scopes', function () {
    $article = Article::factory()->make();

    $user = userWithRole('super-admin', [], $article->user);
    Passport::actingAs($user);

    $this->jsonApi()
        ->withData(jsonData($article))
        ->post(route('api.v1.articles.store'))
        ->assertCreated();

    $this->assertDatabaseHas('articles', [
        'title' => $article->title,
        'slug' => $article->slug,
    ]);
});

it('a super-admin can update any article without permissions or scopes', function () {
    $article = Article::factory()->create();
    $article->title = 'Title changed';

    $user = userWithRole('super-admin', []);
    Passport::actingAs($user);

    $this->jsonApi()
        ->withData(jsonData($article))
        ->patch(route('api.v1.articles.update', $article))
        ->assertOk();

    $this->assertDatabaseHas('articles', [
        'id' => $article->id,
        'title' => 'Title changed',
    ]);
});

it('a super-admin can delete any article without permissions or scopes', function () {
    $article = Article::factory()->create();

    $user = userWithRole('super-admin', []);
    Passport::actingAs($user);

    $this->jsonApi()
        ->delete(route('api.v1.articles.destroy', $article))
        ->assertNoContent();

    $this->assertModelMissing($article);
});

it('a super-admin can create categories without permissions or scopes', function () {
    $category = Category::factory()->make();

    $user = userWithRole('super-admin', []);
    Passport::actingAs($user);

    $this->jsonApi()
        ->withData(jsonData($category))
        ->post(route('api.v1.categories.store'))
        ->assertCreated();

    $this->assertDatabaseHas('categories', [
        'name' => $category->name,
        'slug' => $category->slug,
    ]);
});

it('a super-admin can update any category without permissions or scopes', function () {
    $category = Category::factory()->create();

    $user = userWithRole('super-admin', []);
    Passport::actingAs($user);

    $this->jsonApi()
        ->withData([
            'type' => 'categories',
            'id' => $category->getRouteKey(),
            'attributes' => ['name' => 'Name changed', 'slug' => 'slug-changed'],
        ])
        ->patch(route('api.v1.categories.update', $category))
        ->assertOk();

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Name changed',
        'slug' => 'slug-changed',
    ]);
});

it('a super-admin can delete any category without permissions or scopes', function () {
    $category = Category::factory()->create();

    $user = userWithRole('super-admin', []);
    Passport::actingAs($user);

    $this->jsonApi()
        ->delete(route('api.v1.categories.destroy', $category))
        ->assertNoContent();

    $this->assertModelMissing($category);
});

it('a super-admin can update articles they do not own', function () {
    $article = Article::factory()->create();
    $article->title = 'Title changed';

    $superAdmin = userWithRole('super-admin', []);
    Passport::actingAs($superAdmin);

    $this->jsonApi()
        ->withData(jsonData($article))
        ->patch(route('api.v1.articles.update', $article))
        ->assertOk();
});
