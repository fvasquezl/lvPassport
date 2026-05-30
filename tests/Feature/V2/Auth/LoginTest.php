<?php

use App\Models\User;

it('can login with valid credentials and receives read scope by default', function () {
    $user = User::factory()->create([
        'password' => bcrypt('secret'),
    ]);

    $this->postJson(route('api.v2.login'), [
        'email' => $user->email,
        'password' => 'secret',
    ])
        ->assertOk()
        ->assertJsonStructure(['token', 'user'])
        ->assertJsonPath('user.email', $user->email);
});

it('issues token with explicit scopes when scopes are provided', function () {
    $user = User::factory()->create([
        'password' => bcrypt('secret'),
    ]);

    $response = $this->postJson(route('api.v2.login'), [
        'email' => $user->email,
        'password' => 'secret',
        'scopes' => ['read'],
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token']);
});

it('returns 401 with invalid credentials', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => bcrypt('correct'),
    ]);

    $this->postJson(route('api.v2.login'), [
        'email' => 'user@example.com',
        'password' => 'wrong',
    ])->assertUnauthorized();
});

it('requires email field', function () {
    $this->postJson(route('api.v2.login'), [
        'password' => 'secret',
    ])->assertUnprocessable();
});

it('requires password field', function () {
    $this->postJson(route('api.v2.login'), [
        'email' => 'user@example.com',
    ])->assertUnprocessable();
});

it('never issues wildcard tokens', function () {
    $user = User::factory()->create([
        'password' => bcrypt('secret'),
    ]);

    // Login without scopes — should default to ['read'], never ['*']
    $this->postJson(route('api.v2.login'), [
        'email' => $user->email,
        'password' => 'secret',
    ])->assertOk();

    // Verify the token stored has scopes = ['read'], not ['*']
    $token = $user->tokens()->latest()->first();
    expect($token->scopes)->not->toContain('*')
        ->and($token->scopes)->toContain('read');
});
