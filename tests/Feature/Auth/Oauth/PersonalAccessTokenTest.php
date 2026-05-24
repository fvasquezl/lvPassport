<?php

use App\Models\User;

it('a PAT can access a protected endpoint', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['articles:show'])->accessToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.v1.user'))
        ->assertOk()
        ->assertJson(['email' => $user->email]);
});
