<?php

use App\Models\User;

it('can logout and token is revoked', function () {
    $user = User::factory()->create();
    $result = $user->createToken('test-device-v2');

    $this->withHeader('Authorization', 'Bearer '.$result->accessToken)
        ->postJson(route('api.v2.logout'))
        ->assertNoContent();

    expect($result->token->fresh()->revoked)->toBeTrue();
});

it('guest cannot logout', function () {
    $this->postJson(route('api.v2.logout'))->assertUnauthorized();
});
