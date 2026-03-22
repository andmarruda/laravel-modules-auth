<?php

namespace Andmarruda\AuthModule\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserController extends Controller
{
    public function __construct(private AuthController $authController)
    {
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $response = $this->authController->register($request);
        $data = $response->getData(true);

        if (($response->getStatusCode() >= 400) || !is_array($data)) {
            return $response;
        }

        return response()->json([
            'data' => [
                'id' => $data['id'] ?? data_get($data, 'user.id'),
                'name' => $data['name'] ?? data_get($data, 'user.name'),
                'email' => $data['email'] ?? data_get($data, 'user.email'),
            ],
        ], $response->getStatusCode(), $response->headers->all());
    }
}
