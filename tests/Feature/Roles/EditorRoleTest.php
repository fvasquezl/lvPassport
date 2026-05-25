<?php

use App\Models\Article;
use App\Models\Category;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

$editorPermissions = [
    'articles:store',
    'articles:update',
    'articles:delete',
    'articles:update-authors',
    'articles:update-categories',
];

beforeEach(function () use ($editorPermissions) {
    foreach ([...$editorPermissions, 'categories:store', 'categories:update', 'categories:delete'] as $permission) {
        Permission::findOrCreate($permission, 'api');
    }
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('an editor can create articles', function () use ($editorPermissions) {
    $article = Article::factory()->make();

    $user = userWithRole('editor', $editorPermissions, $article->user);
    Passport::actingAs($user, ['articles:store']);

    $this->jsonApi()
        ->withData(jsonData($article))
        ->post(route('api.v1.articles.store'))
        ->assertCreated();

    $this->assertDatabaseHas('articles', [
        'title' => $article->title,
        'slug' => $article->slug,
    ]);
});

it('an editor can update their own articles', function () use ($editorPermissions) {
    $article = Article::factory()->create();
    $article->title = 'Title changed';

    $user = userWithRole('editor', $editorPermissions, $article->user);
    Passport::actingAs($user, ['articles:update']);

    $this->jsonApi()
        ->withData(jsonData($article))
        ->patch(route('api.v1.articles.update', $article))
        ->assertOk();

    $this->assertDatabaseHas('articles', [
        'id' => $article->id,
        'title' => 'Title changed',
    ]);
});

it('an editor can delete their own articles', function () use ($editorPermissions) {
    $article = Article::factory()->create();

    $user = userWithRole('editor', $editorPermissions, $article->user);
    Passport::actingAs($user, ['articles:delete']);

    $this->jsonApi()
        ->delete(route('api.v1.articles.destroy', $article))
        ->assertNoContent();

    $this->assertModelMissing($article);
});

it('an editor cannot create categories', function () use ($editorPermissions) {
    $data = jsonData(Category::factory()->make());

    $user = userWithRole('editor', $editorPermissions);
    Passport::actingAs($user, ['categories:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertForbidden();

    $this->assertDatabaseEmpty('categories');
});

it('an editor cannot update categories', function () use ($editorPermissions) {
    $category = Category::factory()->create();

    $user = userWithRole('editor', $editorPermissions);
    Passport::actingAs($user, ['categories:update']);

    $this->jsonApi()
        ->withData([
            'type' => 'categories',
            'id' => $category->getRouteKey(),
            'attributes' => ['name' => 'Name changed', 'slug' => 'slug-changed'],
        ])
        ->patch(route('api.v1.categories.update', $category))
        ->assertForbidden();

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => $category->name,
        'slug' => $category->slug,
    ]);
});

it('an editor cannot delete categories', function () use ($editorPermissions) {
    $category = Category::factory()->create();

    $user = userWithRole('editor', $editorPermissions);
    Passport::actingAs($user, ['categories:delete']);

    $this->jsonApi()
        ->delete(route('api.v1.categories.destroy', $category))
        ->assertForbidden();

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});
