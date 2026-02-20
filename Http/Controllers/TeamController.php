<?php

namespace Andmarruda\AuthModule\Http\Controllers;

use Andmarruda\AuthModule\Models\Team;
use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Support\AuthenticatedUserResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request);
        if (!$user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:teams,slug'],
        ]);

        $team = Team::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? (Str::slug($validated['name']) . '-' . Str::lower(Str::random(6))),
            'owner_id' => $user->id,
        ]);

        $team->users()->syncWithoutDetaching([
            $user->id => [
                'role' => 'owner',
                'joined_at' => now(),
            ],
        ]);

        return response()->json([
            'data' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'owner_id' => $team->owner_id,
            ],
        ], 201);
    }

    public function mine(Request $request): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request);
        if (!$user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $teams = $user->teams()
            ->select('teams.id', 'teams.name', 'teams.slug', 'teams.owner_id')
            ->get()
            ->map(static fn (Team $team): array => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'owner_id' => $team->owner_id,
                'role' => $team->pivot?->role,
            ]);

        return response()->json([
            'data' => $teams,
        ]);
    }

    private function resolveAuthenticatedUser(Request $request): ?User
    {
        return AuthenticatedUserResolver::resolve($request);
    }
}
