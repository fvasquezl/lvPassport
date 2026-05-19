<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Permission::findOrCreate('articles:update', 'api');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('guest users cannot update articles', closure: function () {
    $article = Article::factory()->create();

    $data = jsonData(
        Article::factory()->make(['id' => $article->getRouteKey()])
    );

    $this->jsonApi()
        ->withData($data)
        ->patch(route('api.v1.articles.update', $article))
        ->assertUnauthorized(); // 401
});

it('authenticated users can update their articles', function () {
    $article = Article::factory()->create();
    $article->title = 'Title changed';
    $article->content = 'Content changed';

    $data = jsonData($article);

    Passport::actingAs(userWithPermission('articles:update', $article->user));

    $this->jsonApi()
        ->withData($data)
        ->patch(route('api.v1.articles.update', $article))
        ->assertOK(); // 200

    $this->assertDatabaseHas('articles', [
        'title' => 'Title changed',
        'slug' => $article->slug,
        'content' => 'Content changed',
    ]);
});

it('authenticated users cannot update their articles without permissions', function () {
    $article = Article::factory()->create();
    $article->title = 'Title changed';
    $article->content = 'Content changed';

    $data = jsonData($article);

    Passport::actingAs($article->user);

    $this->jsonApi()
        ->withData($data)
        ->patch(route('api.v1.articles.update', $article))
        ->assertForbidden(); // 403

    $this->assertDatabaseMissing('articles', [
        'title' => 'Title changed',
        'content' => 'Content changed',
    ]);
});

it('authenticated users cannot update other articles', function () {
    $article = Article::factory()->create();
    $article->title = 'Title changed';
    $article->content = 'Content changed';

    $data = jsonData($article);

    Passport::actingAs(User::factory()->create());

    $this->jsonApi()
        ->withData($data)
        ->patch(route('api.v1.articles.update', $article))
        ->assertForbidden(); // 403

    $this->assertDatabaseMissing('articles', [
        'title' => 'Title changed',
        'content' => 'Content changed',
    ]);
});

it('authenticated users can update single attribute', function (array $attributes) {
    $article = Article::factory()->create();
    $user = userWithPermission('articles:update', $article->user);

    Passport::actingAs($user);

    $this->jsonApi()
        ->withData([
            'type' => 'articles',
            'id' => $article->getRouteKey(),
            'attributes' => $attributes,
        ])
        ->patch(route('api.v1.articles.update', $article))
        ->assertOk();

    $this->assertDatabaseHas('articles', $attributes + ['id' => $article->id]);
})
    ->with([
        'title only' => [['title' => 'Title changed']],
        'slug only' => [['slug' => 'slug-changed']],
    ]);

it('can replace the categories', function () {
    $article = Article::factory()->create();
    $category = Category::factory()->create();

    Passport::actingAs(
        userWithPermission('articles:update-categories', $article->user)
    );

    $this->jsonApi()
        ->withData([
            'type' => 'categories',
            'id' => (string) $category->getRouteKey(),
        ])
        ->patch(route('api.v1.articles.categories.update', $article))
        ->assertOK(); // 200

    $this->assertDatabaseHas('articles', [
        'id' => $article->id,
        'category_id' => $category->id,
    ]);
});

it('can replace the author', function () {
    $article = Article::factory()->create();
    $author = User::factory()->create();

    Passport::actingAs(
        userWithPermission('articles:update-authors', $article->user)
    );

    $this->jsonApi()

        ->withData([
            'type' => 'authors',
            'id' => $author->getRouteKey(),
        ])
        ->patch(route('api.v1.articles.authors.update', $article))
        ->assertStatus(200);

    $this->assertDatabaseHas('articles', [
        'user_id' => $author->id,
    ]);
});
