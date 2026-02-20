<?php

namespace Andmarruda\AuthModule\Tests\Feature;

use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Support\AuthMiddleware;
use Andmarruda\AuthModule\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

class RouteGuardConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_only_route_denies_sanctum_authenticated_user(): void
    {
        config()->set('authmodule.auth.teams_guards', ['web']);

        Route::post('/__test/web-only', static fn () => response()->json(['ok' => true]))
            ->middleware(AuthMiddleware::fromGuards(
                (array) config('authmodule.auth.teams_guards', ['web']),
            ));

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/__test/web-only');

        $response->assertStatus(401);
    }

    public function test_web_only_route_allows_web_authenticated_user(): void
    {
        config()->set('authmodule.auth.teams_guards', ['web']);

        Route::post('/__test/web-only', static fn () => response()->json(['ok' => true]))
            ->middleware(AuthMiddleware::fromGuards(
                (array) config('authmodule.auth.teams_guards', ['web']),
            ));

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'web')
            ->postJson('/__test/web-only');

        $response->assertOk()
            ->assertJsonPath('ok', true);
    }
}
