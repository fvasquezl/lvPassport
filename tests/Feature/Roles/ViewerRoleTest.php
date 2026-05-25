<?php

use App\Models\Article;
use App\Models\Category;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    foreach (['articles:store', 'articles:update', 'articles:delete', 'categories:store', 'categories:update', 'categories:delete'] as $permission) {
        Permission::findOrCreate($permission, 'api');
    }
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('a viewer cannot create articles even with the correct scope', function () {
    $data = jsonData(Article::factory()->make());

    $user = userWithRole('viewer', []);
    Passport::actingAs($user, ['articles:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.articles.store'))
        ->assertForbidden();

    $this->assertDatabaseEmpty('articles');
});

it('a viewer cannot update articles even with the correct scope', function () {
    $article = Article::factory()->create();
    $originalTitle = $article->title;
    $article->title = 'Title changed';

    $user = userWithRole('viewer', [], $article->user);
    Passport::actingAs($user, ['articles:update']);

    $this->jsonApi()
        ->withData(jsonData($article))
        ->patch(route('api.v1.articles.update', $article))
        ->assertForbidden();

    $this->assertDatabaseHas('articles', [
        'id' => $article->id,
        'title' => $originalTitle,
    ]);
});

it('a viewer cannot delete articles even with the correct scope', function () {
    $article = Article::factory()->create();

    $user = userWithRole('viewer', [], $article->user);
    Passport::actingAs($user, ['articles:delete']);

    $this->jsonApi()
        ->delete(route('api.v1.articles.destroy', $article))
        ->assertForbidden();

    $this->assertDatabaseHas('articles', ['id' => $article->id]);
});

it('a viewer cannot create categories even with the correct scope', function () {
    $data = jsonData(Category::factory()->make());

    $user = userWithRole('viewer', []);
    Passport::actingAs($user, ['categories:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v1.categories.store'))
        ->assertForbidden();

    $this->assertDatabaseEmpty('categories');
});

it('a viewer cannot update categories even with the correct scope', function () {
    $category = Category::factory()->create();

    $user = userWithRole('viewer', []);
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

it('a viewer cannot delete categories even with the correct scope', function () {
    $category = Category::factory()->create();

    $user = userWithRole('viewer', []);
    Passport::actingAs($user, ['categories:delete']);

    $this->jsonApi()
        ->delete(route('api.v1.categories.destroy', $category))
        ->assertForbidden();

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});
