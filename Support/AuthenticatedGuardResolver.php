<?php

namespace Andmarruda\AuthModule\Support;

use Andmarruda\AuthModule\Models\User;
use Illuminate\Http\Request;

class AuthenticatedGuardResolver
{
    public static function resolve(Request $request): ?string
    {
        $guards = array_values(array_unique(array_filter([
            config('authmodule.auth.default_guard', 'web'),
            config('authmodule.auth.session_guard', 'web'),
            config('authmodule.auth.api_guard', 'sanctum'),
            config('authmodule.auth.jwt_guard', 'jwt'),
        ], static fn (mixed $guard): bool => is_string($guard) && $guard !== '')));

        foreach ($guards as $guard) {
            if (!is_array(config("auth.guards.{$guard}"))) {
                continue;
            }

            $candidate = $request->user($guard);
            if ($candidate instanceof User) {
                return $guard;
            }
        }

        return null;
    }

    public static function guardToChannel(?string $guard): string
    {
        return match ($guard) {
            config('authmodule.auth.jwt_guard', 'jwt') => 'jwt',
            config('authmodule.auth.api_guard', 'sanctum') => 'sanctum',
            default => 'session',
        };
    }
}
