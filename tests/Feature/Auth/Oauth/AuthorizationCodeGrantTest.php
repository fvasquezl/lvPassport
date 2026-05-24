<?php

use App\Models\User;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

beforeEach(function () {
    Passport::authorizationView(fn ($parameters) => response()->json($parameters));
});

it('issues access token via authorization code with PKCE', function () {
    $user = User::factory()->create();

    $client = Client::factory()->asPublic()->create([
        'redirect_uris' => ['http://localhost/auth/callback'],
    ]);

    $codeVerifier = bin2hex(random_bytes(64));
    $codeChallenge = rtrim(strtr(
        base64_encode(hash('sha256', $codeVerifier, true)),
        '+/', '-_'
    ), '=');

    // 1. GET /oauth/authorize → renderiza la "vista" (ahora JSON)
    $authResponse = $this->actingAs($user)->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->id,
        'redirect_uri' => 'http://localhost/auth/callback',
        'response_type' => 'code',
        'scope' => 'articles:show',
        'code_challenge' => $codeChallenge,
        'code_challenge_method' => 'S256',
        'state' => 'xyz',
    ]));
    $authResponse->assertOk();
    $authToken = $authResponse->json('authToken');

    // 2. POST /oauth/authorize → aprueba y redirige con ?code=...
    $approve = $this->post('/oauth/authorize', [
        'auth_token' => $authToken,
        'state' => 'xyz',
    ]);
    $approve->assertRedirect();

    parse_str(parse_url($approve->headers->get('Location'), PHP_URL_QUERY), $params);
    expect($params)->toHaveKey('code');

    // 3. Intercambio code → tokens
    $tokenResponse = $this->postJson('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $client->id,
        'redirect_uri' => 'http://localhost/auth/callback',
        'code_verifier' => $codeVerifier,
        'code' => $params['code'],
    ]);

    $tokenResponse->assertOk();
    expect($tokenResponse->json('access_token'))->not->toBeEmpty()
        ->and($tokenResponse->json('refresh_token'))->not->toBeEmpty()
        ->and($tokenResponse->json('token_type'))->toBe('Bearer');
});
