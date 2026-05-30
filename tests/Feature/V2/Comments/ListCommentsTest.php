<?php

use App\Models\Comment;
use App\Models\User;
use Laravel\Passport\Passport;

it('guest users can fetch a single comment', function () {
    $comment = Comment::factory()->create();

    $this->jsonApi()
        ->get(route('api.v2.comments.show', $comment))
        ->assertOk();
});

it('authenticated users can fetch a single comment', function () {
    $comment = Comment::factory()->create();

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()->get(route('api.v2.comments.show', $comment))
        ->assertOk()
        ->assertJson([
            'data' => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
                'attributes' => [
                    'body' => $comment->body,
                    'createdAt' => $comment->created_at->toJSON(),
                    'updatedAt' => $comment->updated_at->toJSON(),
                ],
                'links' => [
                    'self' => route('api.v2.comments.show', $comment),
                ],
            ],
        ]);
});

it('authenticated users can fetch a single comment without scope', function () {
    $comment = Comment::factory()->create();

    Passport::actingAs(User::factory()->create());

    $this->jsonApi()
        ->get(route('api.v2.comments.show', $comment))
        ->assertOk();
});

it('guest users can fetch all comments', function () {
    Comment::factory()->count(3)->create();

    $this->jsonApi()
        ->get(route('api.v2.comments.index'))
        ->assertOk();
});

it('authenticated users can fetch all comments without scope', function () {
    Comment::factory()->count(3)->create();

    Passport::actingAs(User::factory()->create());

    $this->jsonApi()
        ->get(route('api.v2.comments.index'))
        ->assertOk();
});

it('can fetch all comments', function () {
    Comment::factory()->count(3)->create();

    Passport::actingAs(User::factory()->create(), ['read']);

    $this->jsonApi()->get(route('api.v2.comments.index'))
        ->assertOk()
        ->assertJsonCount(3, 'data');
});
