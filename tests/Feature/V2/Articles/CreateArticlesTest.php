<?php

use App\Models\Article;
use App\Models\User;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Permission::findOrCreate('articles:store', 'api');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('guest users cannot create articles', function () {
    $data = jsonData(Article::factory()->make());

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v2.articles.store'))
        ->assertUnauthorized();

    $this->assertDatabaseEmpty('articles');
});

it('returns json errors when no data is sent', function () {
    $user = userWithPermission('articles:store');
    Passport::actingAs($user, ['articles:store']);

    $this->jsonApi()
        ->withData([])
        ->post(route('api.v2.articles.store'))
        ->assertBadRequest()
        ->assertJsonPath('errors.0.source.pointer', '/data');
});

it('authenticated users can create articles', function () {
    $data = jsonData(
        $article = Article::factory()->make()
    );

    $user = userWithPermission('articles:store', $article->user);
    Passport::actingAs($user, ['articles:store']);

    $response = $this->jsonApi()
        ->withData($data)
        ->post(route('api.v2.articles.store'))
        ->assertCreated();

    expect($response->json('data.attributes'))
        ->title->toBe($article->title)
        ->slug->toBe($article->slug)
        ->content->toBe($article->content);

    $this->assertDatabaseHas('articles', [
        'title' => $article->title,
        'slug' => $article->slug,
        'content' => $article->content,
        'user_id' => $article->user->id,
    ]);
});

it('authenticated users without scope cannot create articles', function () {
    $data = jsonData($article = Article::factory()->make());

    $user = userWithPermission('articles:store', $article->user);
    Passport::actingAs($user);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v2.articles.store'))
        ->assertForbidden();

    $this->assertDatabaseEmpty('articles');
});

it('authenticated users cannot create articles without permissions', function () {
    $data = jsonData($article = Article::factory()->make());

    Passport::actingAs($article->user, ['articles:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v2.articles.store'))
        ->assertForbidden();

    $this->assertDatabaseEmpty('articles');
});

it('authenticated users cannot create articles on behalf of other user', function () {
    $data = jsonData($article = Article::factory()->make());
    $data['relationships']['authors']['data']['id'] = User::factory()->create()->getRouteKey();

    $user = userWithPermission('articles:store', $article->user);
    Passport::actingAs($user, ['articles:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v2.articles.store'))
        ->assertForbidden();

    $this->assertDatabaseEmpty('articles');
});

it('rejects unknown attributes', function () {
    $data = jsonData($article = Article::factory()->make());
    $data['attributes']['approved'] = true;

    $user = userWithPermission('articles:store', $article->user);
    Passport::actingAs($user, ['articles:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v2.articles.store'))
        ->assertBadRequest();

    $this->assertDatabaseEmpty('articles');
});

it('users without permission get 403 even when authors relationship is missing', function () {
    $data = jsonData($article = Article::factory()->make());
    unset($data['relationships']['authors']);

    Passport::actingAs($article->user, ['articles:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v2.articles.store'))
        ->assertForbidden();

    $this->assertDatabaseEmpty('articles');
});

it('authors is required', function () {
    $data = jsonData($article = Article::factory()->make());
    unset($data['relationships']['authors']);

    $user = userWithPermission('articles:store', $article->user);
    Passport::actingAs($user, ['articles:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v2.articles.store'))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.source.pointer', '/data/relationships/authors');

    $this->assertDatabaseEmpty('articles');
});

it('categories is required', function () {
    $data = jsonData($article = Article::factory()->make());
    unset($data['relationships']['categories']);

    $user = userWithPermission('articles:store', $article->user);
    Passport::actingAs($user, ['articles:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v2.articles.store'))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.source.pointer', '/data/relationships/categories');

    $this->assertDatabaseEmpty('articles');
});

it('relationship must be a valid type', function (string $relationship, string $wrongType) {
    $data = jsonData($article = Article::factory()->make());
    $data['relationships'][$relationship] = [
        'data' => ['type' => $wrongType, 'id' => '1'],
    ];

    $user = userWithPermission('articles:store', $article->user);
    Passport::actingAs($user, ['articles:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v2.articles.store'))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.source.pointer', "/data/relationships/$relationship");
})
    ->with([
        'authors with categories type' => ['authors', 'categories'],
        'categories with authors type' => ['categories', 'authors'],
    ]);

it('rejects empty required attributes', function (string $field) {
    $data = jsonData($article = Article::factory()->make([$field => '']));

    $user = userWithPermission('articles:store', $article->user);
    Passport::actingAs($user, ['articles:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v2.articles.store'))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.source.pointer', "/data/attributes/$field");

    $this->assertDatabaseEmpty('articles');
})
    ->with([
        'title required' => 'title',
        'content required' => 'content',
    ]);

it('slug must be unique', function () {
    Article::factory()->create(['slug' => 'same-slug']);

    $data = jsonData(
        $article = Article::factory()->make(['slug' => 'same-slug'])
    );

    $user = userWithPermission('articles:store', $article->user);
    Passport::actingAs($user, ['articles:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v2.articles.store'))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.source.pointer', '/data/attributes/slug');

    $this->assertDatabaseCount('articles', 1);
});

it('rejects invalid slugs', function (string $slug) {
    $data = jsonData($article = Article::factory()->make(['slug' => $slug]));

    $user = userWithPermission('articles:store', $article->user);
    Passport::actingAs($user, ['articles:store']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v2.articles.store'))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.source.pointer', '/data/attributes/slug');

    $this->assertDatabaseEmpty('articles');
})
    ->with([
        'empty' => '',
        'invalid characters' => '%$%#@',
        'contains underscores' => 'with_underscores',
        'starts with dash' => '-start-with-dash',
        'ends with dash' => 'end-with-dash-',
    ]);
