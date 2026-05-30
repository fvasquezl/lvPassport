<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * V2 Login Controller.
 *
 * Issues tokens with explicit, documented OAuth 2.0 scopes only.
 * Defaults to ['read'] when no scopes are provided in the request.
 * Never issues wildcard ['*'] tokens.
 */
class LoginController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'scopes' => ['sometimes', 'array'],
            'scopes.*' => ['string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        $user = Auth::user();
        $scopes = $request->input('scopes', ['read']);
        $token = $user->createToken('api-token-v2', $scopes)->accessToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
