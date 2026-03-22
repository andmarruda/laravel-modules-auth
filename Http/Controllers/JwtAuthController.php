<?php

namespace Andmarruda\AuthModule\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class JwtAuthController extends Controller
{
    public function __construct(private AuthController $authController)
    {
    }

    public function token(Request $request): JsonResponse
    {
        $request->merge(['channel' => 'jwt']);

        $response = $this->authController->login($request);
        $data = $response->getData(true);

        if (($response->getStatusCode() >= 400) || !is_array($data)) {
            return $response;
        }

        return response()->json([
            'data' => [
                'access_token' => $data['token'] ?? null,
                'token_type' => $data['type'] ?? null,
                'expires_in' => $data['expires_in'] ?? null,
                'user' => $data['user'] ?? null,
                'roles' => $data['roles'] ?? [],
                'permissions' => $data['permissions'] ?? [],
                'plan' => $data['plan'] ?? null,
            ],
        ], $response->getStatusCode(), $response->headers->all());
    }
}
