<?php

use App\Models\Comment;
use App\Models\User;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Permission::findOrCreate('comments:update', 'api');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

/**
 * @return array<string, mixed>
 */
function updateCommentData(Comment $comment, string $body = 'Body changed'): array
{
    return [
        'type' => 'comments',
        'id' => (string) $comment->getRouteKey(),
        'attributes' => ['body' => $body],
    ];
}

it('guest users cannot update comments', function () {
    $comment = Comment::factory()->create();

    $this->jsonApi()
        ->withData(updateCommentData($comment))
        ->patch(route('api.v2.comments.update', $comment))
        ->assertUnauthorized();

    $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => $comment->body]);
});

it('authenticated users can update their own comment', function () {
    $user = userWithPermission('comments:update');
    $comment = Comment::factory()->create(['user_id' => $user->id]);
    Passport::actingAs($user, ['comments:update']);

    $this->jsonApi()
        ->withData(updateCommentData($comment))
        ->patch(route('api.v2.comments.update', $comment))
        ->assertOk();

    $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'Body changed']);
});

it('authenticated users without scope cannot update their comment', function () {
    $user = userWithPermission('comments:update');
    $comment = Comment::factory()->create(['user_id' => $user->id]);
    Passport::actingAs($user);

    $this->jsonApi()
        ->withData(updateCommentData($comment))
        ->patch(route('api.v2.comments.update', $comment))
        ->assertForbidden();

    $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => $comment->body]);
});

it('authenticated users without permission cannot update their comment', function () {
    $user = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $user->id]);
    Passport::actingAs($user, ['comments:update']);

    $this->jsonApi()
        ->withData(updateCommentData($comment))
        ->patch(route('api.v2.comments.update', $comment))
        ->assertForbidden();

    $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => $comment->body]);
});

it('authenticated users cannot update other users comments', function () {
    $user = userWithPermission('comments:update');
    $comment = Comment::factory()->create(); // belongs to someone else
    Passport::actingAs($user, ['comments:update']);

    $this->jsonApi()
        ->withData(updateCommentData($comment))
        ->patch(route('api.v2.comments.update', $comment))
        ->assertForbidden();

    $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => $comment->body]);
});

it('a super-admin can update any users comment', function () {
    $comment = Comment::factory()->create(); // belongs to someone else
    Passport::actingAs(userWithRole('super-admin', []));

    $this->jsonApi()
        ->withData(updateCommentData($comment))
        ->patch(route('api.v2.comments.update', $comment))
        ->assertOk();

    $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'Body changed']);
});
