<?php

namespace Andmarruda\AuthModule\Http\Controllers;

use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Support\AuthenticatedUserResolver;
use Andmarruda\AuthModule\Support\UserSettingsManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserSettingsController extends Controller
{
    public function __construct(private UserSettingsManager $settings)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $user = AuthenticatedUserResolver::resolve($request);

        if (!$user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'data' => $this->settings->all($user),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = AuthenticatedUserResolver::resolve($request);

        if (!$user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate($this->settings->validationRules());
        $settings = $this->settings->update($user, $validated);

        return response()->json([
            'data' => $settings,
        ]);
    }
}
