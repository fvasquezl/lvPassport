<?php

use App\Models\User;
use Laravel\Passport\Passport;

it('can fetch the authenticated user', function () {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $this->getJson(route('api.v1.user'))
        ->assertOk()
        ->assertJson([
            'email' => $user->email,
        ]);
});

it('guest cannot fetch any user', function () {
    $this->getJson(route('api.v1.user'))
        ->assertUnauthorized();
});

it('returns 200 with empty scopes since /user does not require any scope', function () {
    Passport::actingAs(User::factory()->create(), []);
    $this->getJson(route('api.v1.user'))->assertOk();
});
