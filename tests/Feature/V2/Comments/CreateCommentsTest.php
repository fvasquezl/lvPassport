<?php

use App\Models\Article;
use App\Models\User;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Permission::findOrCreate('comments:store', 'api');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

/**
 * Build a valid comments JSON:API payload authored by $author on $article.
 *
 * @return array<string, mixed>
 */
function commentData(User $author, Article $article, string $body = 'Great article!'): array
{
    return [
        'type' => 'comments',
        'attributes' => ['body' => $body],
        'relationships' => [
            'author' => ['data' => ['type' => 'authors', 'id' => $author->getRouteKey()]],
            'article' => ['data' => ['type' => 'articles', 'id' => $article->getRouteKey()]],
        ],
    ];
}

it('guest users cannot create comments', function () {
    $article = Article::factory()->create();

    $this->jsonApi()
        ->withData(commentData(User::factory()->create(), $article))
        ->post(route('api.v2.comments.store'))
        ->assertUnauthorized();

    $this->assertDatabaseEmpty('comments');
});

it('authenticated users can create comments', function () {
    $article = Article::factory()->create();
    $user = userWithPermission('comments:store');
    Passport::actingAs($user, ['comments:store']);

    $this->jsonApi()
        ->withData(commentData($user, $article))
        ->post(route('api.v2.comments.store'))
        ->assertCreated();

    $this->assertDatabaseHas('comments', [
        'body' => 'Great article!',
        'user_id' => $user->id,
        'article_id' => $article->id,
    ]);
});

it('authenticated users without scope cannot create comments', function () {
    $article = Article::factory()->create();
    $user = userWithPermission('comments:store');
    Passport::actingAs($user);

    $this->jsonApi()
        ->withData(commentData($user, $article))
        ->post(route('api.v2.comments.store'))
        ->assertForbidden();

    $this->assertDatabaseEmpty('comments');
});

it('authenticated users without permission cannot create comments', function () {
    $article = Article::factory()->create();
    $user = User::factory()->create();
    Passport::actingAs($user, ['comments:store']);

    $this->jsonApi()
        ->withData(commentData($user, $article))
        ->post(route('api.v2.comments.store'))
        ->assertForbidden();

    $this->assertDatabaseEmpty('comments');
});

it('authenticated users cannot create comments on behalf of other user', function () {
    $article = Article::factory()->create();
    $user = userWithPermission('comments:store');
    Passport::actingAs($user, ['comments:store']);

    $this->jsonApi()
        ->withData(commentData(User::factory()->create(), $article))
        ->post(route('api.v2.comments.store'))
        ->assertForbidden();

    $this->assertDatabaseEmpty('comments');
});

it('body is required', function () {
    $article = Article::factory()->create();
    $user = userWithPermission('comments:store');
    Passport::actingAs($user, ['comments:store']);

    $this->jsonApi()
        ->withData(commentData($user, $article, ''))
        ->post(route('api.v2.comments.store'))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.source.pointer', '/data/attributes/body');

    $this->assertDatabaseEmpty('comments');
});

it('author is required', function () {
    $article = Article::factory()->create();
    $user = userWithPermission('comments:store');
    Passport::actingAs($user, ['comments:store']);

    $data = commentData($user, $article);
    unset($data['relationships']['author']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v2.comments.store'))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.source.pointer', '/data/relationships/author');

    $this->assertDatabaseEmpty('comments');
});

it('article is required', function () {
    $article = Article::factory()->create();
    $user = userWithPermission('comments:store');
    Passport::actingAs($user, ['comments:store']);

    $data = commentData($user, $article);
    unset($data['relationships']['article']);

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v2.comments.store'))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.source.pointer', '/data/relationships/article');

    $this->assertDatabaseEmpty('comments');
});

it('cannot create a comment on a non-existent article', function () {
    $user = userWithPermission('comments:store');
    Passport::actingAs($user, ['comments:store']);

    $data = [
        'type' => 'comments',
        'attributes' => ['body' => 'Great article!'],
        'relationships' => [
            'author' => ['data' => ['type' => 'authors', 'id' => $user->getRouteKey()]],
            'article' => ['data' => ['type' => 'articles', 'id' => 'non-existent-article']],
        ],
    ];

    $this->jsonApi()
        ->withData($data)
        ->post(route('api.v2.comments.store'))
        ->assertNotFound();

    $this->assertDatabaseEmpty('comments');
});
