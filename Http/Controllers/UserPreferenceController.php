<?php

namespace Andmarruda\AuthModule\Http\Controllers;

use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Support\AuthenticatedUserResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserPreferenceController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_KEYS = [
        'language',
        'theme',
    ];

    public function upsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100', 'in:' . implode(',', self::ALLOWED_KEYS)],
            'value' => ['nullable', 'string', 'max:255'],
        ]);

        $user = AuthenticatedUserResolver::resolve($request);

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->setPreference($validated['key'], $validated['value'] ?? null);

        return response()->json([
            'success' => true,
            'data' => [
                'key' => $validated['key'],
                'value' => $validated['value'] ?? null,
            ],
        ]);
    }
}
