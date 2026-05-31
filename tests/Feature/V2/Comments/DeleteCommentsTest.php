<?php

use App\Models\Comment;
use App\Models\User;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Permission::findOrCreate('comments:delete', 'api');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('guest users cannot delete comments', function () {
    $comment = Comment::factory()->create();

    $this->jsonApi()
        ->delete(route('api.v2.comments.destroy', $comment))
        ->assertUnauthorized();

    $this->assertDatabaseHas('comments', ['id' => $comment->id]);
});

it('authenticated users can delete their own comment', function () {
    $user = userWithPermission('comments:delete');
    $comment = Comment::factory()->create(['user_id' => $user->id]);
    Passport::actingAs($user, ['comments:delete']);

    $this->jsonApi()
        ->delete(route('api.v2.comments.destroy', $comment))
        ->assertNoContent();

    $this->assertModelMissing($comment);
});

it('authenticated users without scope cannot delete their comment', function () {
    $user = userWithPermission('comments:delete');
    $comment = Comment::factory()->create(['user_id' => $user->id]);
    Passport::actingAs($user);

    $this->jsonApi()
        ->delete(route('api.v2.comments.destroy', $comment))
        ->assertForbidden();

    $this->assertDatabaseHas('comments', ['id' => $comment->id]);
});

it('authenticated users without permission cannot delete their comment', function () {
    $user = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $user->id]);
    Passport::actingAs($user, ['comments:delete']);

    $this->jsonApi()
        ->delete(route('api.v2.comments.destroy', $comment))
        ->assertForbidden();

    $this->assertDatabaseHas('comments', ['id' => $comment->id]);
});

it('authenticated users cannot delete other users comments', function () {
    $user = userWithPermission('comments:delete');
    $comment = Comment::factory()->create(); // belongs to someone else
    Passport::actingAs($user, ['comments:delete']);

    $this->jsonApi()
        ->delete(route('api.v2.comments.destroy', $comment))
        ->assertForbidden();

    $this->assertDatabaseHas('comments', ['id' => $comment->id]);
});

it('a super-admin can delete any users comment', function () {
    $comment = Comment::factory()->create(); // belongs to someone else
    Passport::actingAs(userWithRole('super-admin', []));

    $this->jsonApi()
        ->delete(route('api.v2.comments.destroy', $comment))
        ->assertNoContent();

    $this->assertModelMissing($comment);
});
