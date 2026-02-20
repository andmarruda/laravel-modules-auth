<?php

namespace Andmarruda\AuthModule\Tests\Unit;

use Andmarruda\AuthModule\Support\AuthMiddleware;
use Andmarruda\AuthModule\Tests\TestCase;

class AuthMiddlewareTest extends TestCase
{
    public function test_from_guards_returns_web_and_sanctum_when_both_are_defined(): void
    {
        config()->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        config()->set('auth.guards.sanctum', [
            'driver' => 'session',
            'provider' => 'users',
        ]);

        $middleware = AuthMiddleware::fromGuards(['web', 'sanctum']);

        $this->assertSame('auth:web,sanctum', $middleware);
    }

    public function test_from_guards_ignores_undefined_guard_and_keeps_defined_ones(): void
    {
        config()->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        config()->set('auth.guards.sanctum', null);

        $middleware = AuthMiddleware::fromGuards(['web', 'sanctum']);

        $this->assertSame('auth:web', $middleware);
    }

    public function test_from_guards_uses_fallback_guard_when_no_configured_guard_is_valid(): void
    {
        config()->set('auth.guards.sanctum', null);
        config()->set('auth.guards.custom', [
            'driver' => 'session',
            'provider' => 'users',
        ]);

        $middleware = AuthMiddleware::fromGuards(['sanctum'], 'custom');

        $this->assertSame('auth:custom', $middleware);
    }
}
