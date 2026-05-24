<?php

use App\Models\User;

it('can logout', function () {
    $user = User::factory()->create();
    $result = $user->createToken('test-device');

    $this->withHeader('Authorization', 'Bearer '.$result->accessToken)
        ->postJson(route('api.v1.logout'))
        ->assertNoContent();

    expect($result->token->fresh()->revoked)->toBeTrue();
});

it('guest cannot logout', function () {
    $this->postJson(route('api.v1.logout'))->assertUnauthorized();
});
