<?php

use App\Models\User;
use Laravel\Passport\Passport;

it('can fetch the authenticated user', function () {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $this->getJson(route('api.v2.user'))
        ->assertOk()
        ->assertJson([
            'email' => $user->email,
        ]);
});

it('guest cannot fetch any user', function () {
    $this->getJson(route('api.v2.user'))
        ->assertUnauthorized();
});

it('returns 200 with read scope since /user does not require any specific scope', function () {
    Passport::actingAs(User::factory()->create(), ['read']);

    $this->getJson(route('api.v2.user'))->assertOk();
});
