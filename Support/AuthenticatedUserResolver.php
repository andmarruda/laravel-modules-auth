<?php

namespace Andmarruda\AuthModule\Support;

use Andmarruda\AuthModule\Models\User;
use Illuminate\Http\Request;

class AuthenticatedUserResolver
{
    public static function resolve(Request $request): ?User
    {
        $guards = array_values(array_unique(array_filter([
            config('authmodule.auth.default_guard', 'web'),
            config('authmodule.auth.session_guard', 'web'),
            config('authmodule.auth.api_guard', 'sanctum'),
        ], static fn (mixed $guard): bool => is_string($guard) && $guard !== '')));

        foreach ($guards as $guard) {
            if (!is_array(config("auth.guards.{$guard}"))) {
                continue;
            }

            $candidate = $request->user($guard);
            if ($candidate instanceof User) {
                return $candidate;
            }
        }

        $fallback = $request->user();

        return $fallback instanceof User ? $fallback : null;
    }
}
