<?php

namespace Andmarruda\AuthModule\Support\Auth;

use Andmarruda\AuthModule\Contracts\AuthChannelInterface;
use Andmarruda\AuthModule\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionAuthChannel implements AuthChannelInterface
{
    public function name(): string
    {
        return 'session';
    }

    public function authenticate(User $user, Request $request): array
    {
        $guard = (string) config('authmodule.auth.session_guard', 'web');

        Auth::guard($guard)->login($user, (bool) $request->boolean('remember'));

        if (method_exists($request, 'session') && $request->hasSession()) {
            $request->session()->regenerate();
        }

        return [
            'token' => null,
            'type' => 'session',
            'expires_in' => null,
        ];
    }

    public function refresh(User $user, Request $request): array
    {
        if (method_exists($request, 'session') && $request->hasSession()) {
            $request->session()->regenerate();
        }

        return [
            'token' => null,
            'type' => 'session',
            'expires_in' => null,
        ];
    }

    public function logout(User $user, Request $request): void
    {
        $guard = (string) config('authmodule.auth.session_guard', 'web');

        Auth::guard($guard)->logout();

        if (method_exists($request, 'session') && $request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }
}
