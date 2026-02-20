<?php

namespace Andmarruda\AuthModule\Support;

class AuthMiddleware
{
    /**
     * @param array<int, string> $guards
     */
    public static function fromGuards(array $guards, string $fallback = 'web'): string
    {
        $resolved = array_values(array_filter(
            $guards,
            static fn (string $guard): bool => $guard !== '' && is_array(config("auth.guards.{$guard}")),
        ));

        $fallbackGuard = is_array(config("auth.guards.{$fallback}")) ? $fallback : 'web';

        if ($resolved === []) {
            return "auth:{$fallbackGuard}";
        }

        return 'auth:' . implode(',', $resolved);
    }
}
