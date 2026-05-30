<?php

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;

it('guest users cannot fetch an author', function () {
    $author = User::factory()->create();

    $this->jsonApi()
        ->get(route('api.v2.authors.show', $author))
        ->assertUnauthorized();
});

it('authenticated users can fetch an author with read scope', function () {
    $author = User::factory()->create();

    $user = User::factory()->create();
    Passport::actingAs($user, ['read']);

    $response = $this->jsonApi()->get(route('api.v2.authors.show', $author))
        ->assertOk()
        ->assertJson([
            'data' => [
                'type' => 'authors',
                'id' => $author->getRouteKey(),
                'attributes' => [
                    'name' => $author->name,
                    'email' => $author->email,
                ],
                'links' => [
                    'self' => route('api.v2.authors.show', $author),
                ],
            ],
        ]);

    expect(Str::isUuid($response->json('data.id')))->toBeTrue();
});

it('authenticated users cannot fetch an author without read scope', function () {
    $author = User::factory()->create();

    $user = User::factory()->create();
    Passport::actingAs($user);

    $this->jsonApi()
        ->get(route('api.v2.authors.show', $author))
        ->assertForbidden();
});

it('guest users cannot fetch all authors', function () {
    User::factory()->count(3)->create();

    $this->jsonApi()
        ->get(route('api.v2.authors.index'))
        ->assertUnauthorized();
});

it('authenticated users cannot fetch all authors without read scope', function () {
    User::factory()->count(3)->create();

    $user = User::factory()->create();
    Passport::actingAs($user);

    $this->jsonApi()
        ->get(route('api.v2.authors.index'))
        ->assertForbidden();
});

it('can fetch all authors with read scope', function () {
    User::factory()->count(3)->create();

    $user = User::factory()->create();
    Passport::actingAs($user, ['read']);

    $this->jsonApi()->get(route('api.v2.authors.index'))
        ->assertOk()
        ->assertJsonCount(4, 'data'); // 3 created + the acting user
});
