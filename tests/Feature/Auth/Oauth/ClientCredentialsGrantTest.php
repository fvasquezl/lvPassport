<?php

use Laravel\Passport\ClientRepository;

it('issues access token for client credentials grant', function () {
    $client = app(ClientRepository::class)
        ->createClientCredentialsGrantClient('Test Backend Service');

    // El secret plano sólo está disponible justo después de create
    $plainSecret = $client->plainSecret;

    $response = $this->postJson('/oauth/token', [
        'grant_type' => 'client_credentials',
        'client_id' => $client->id,
        'client_secret' => $plainSecret,
        'scope' => '',
    ]);

    $response->assertOk();
    expect($response->json('access_token'))->not->toBeEmpty()
        ->and($response->json('token_type'))->toBe('Bearer');
});

it('rejects client credentials with wrong secret', function () {
    $client = app(ClientRepository::class)
        ->createClientCredentialsGrantClient('Test Backend Service');

    $this->postJson('/oauth/token', [
        'grant_type' => 'client_credentials',
        'client_id' => $client->id,
        'client_secret' => 'wrong-secret',
    ])->assertUnauthorized();
});
