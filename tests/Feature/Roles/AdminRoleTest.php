<?php

use App\Models\Article;
use App\Models\Category;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

$adminPermissions = [
    'articles:store',
    'articles:update',
    'articles:delete',
    'articles:update-authors',
    'articles:update-categories',
    'categories:store',
    'categories:update',
    'categories:delete',
];

beforeEach(function () use ($adminPermissions) {
    foreach ($adminPermissions as $permission) {
        Permission::findOrCreate($permission, 'api');
    }
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('an admin can create categories', function () use ($adminPermissions) {
    $category = Category::factory()->make();

    $user = userWithRole('admin', $adminPermissions);
    Passport::actingAs($user, ['categories:store']);

    $this->jsonApi()
        ->withData(jsonData($category))
        ->post(route('api.v1.categories.store'))
        ->assertCreated();

    $this->assertDatabaseHas('categories', [
        'name' => $category->name,
        'slug' => $category->slug,
    ]);
});

it('an admin can update categories', function () use ($adminPermissions) {
    $category = Category::factory()->create();

    $user = userWithRole('admin', $adminPermissions);
    Passport::actingAs($user, ['categories:update']);

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

it('an admin can delete categories', function () use ($adminPermissions) {
    $category = Category::factory()->create();

    $user = userWithRole('admin', $adminPermissions);
    Passport::actingAs($user, ['categories:delete']);

    $this->jsonApi()
        ->delete(route('api.v1.categories.destroy', $category))
        ->assertNoContent();

    $this->assertModelMissing($category);
});

it('an admin can also create articles', function () use ($adminPermissions) {
    $article = Article::factory()->make();

    $user = userWithRole('admin', $adminPermissions, $article->user);
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
