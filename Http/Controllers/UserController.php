<?php

namespace App\Modules\AuthModule\Http\Controllers;

use App\Modules\AuthModule\UseCases\Register\RegisterUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserController extends Controller
{
    public function __construct(
        private RegisterUser $registerUser,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $result = $this->registerUser->execute(
            token: $validated['token'],
            name: $validated['name'],
            password: $validated['password'],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (!$result->success) {
            return match ($result->error) {
                'invitation_not_found' => response()->json(['message' => 'Invitation not found.'], 404),
                'invitation_already_used' => response()->json(['message' => 'Invitation already used.'], 410),
                'invitation_expired' => response()->json(['message' => 'Invitation expired.'], 410),
            };
        }

        return response()->json([
            'data' => [
                'id' => $result->user->id,
                'name' => $result->user->name,
                'email' => $result->user->email,
            ],
        ], 201);
    }
}
