<?php

namespace Andmarruda\AuthModule\Tests\Feature;

use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Ports\Services\JwtManagerInterface;
use Andmarruda\AuthModule\Support\AuthMiddleware;
use Andmarruda\AuthModule\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class JwtAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_issue_jwt_token_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'jwt.user@example.com',
            'password' => 'SecurePass123!',
        ]);

        $response = $this->postJson('/auth/jwt/token', [
            'email' => 'jwt.user@example.com',
            'password' => 'SecurePass123!',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id);

        $token = $response->json('data.access_token');
        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $payload = app(JwtManagerInterface::class)->decode($token);
        $this->assertIsArray($payload);
        $this->assertSame((string) $user->id, $payload['sub'] ?? null);
    }

    public function test_cannot_issue_token_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'jwt.user@example.com',
            'password' => 'SecurePass123!',
        ]);

        $this->postJson('/auth/jwt/token', [
            'email' => 'jwt.user@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401)
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_jwt_guard_allows_access_with_valid_token(): void
    {
        Route::get('/__test/jwt-protected', static function (Request $request) {
            return response()->json([
                'email' => $request->user()?->email,
            ]);
        })->middleware(AuthMiddleware::fromGuards(['jwt']));

        $user = User::factory()->create([
            'email' => 'jwt.user@example.com',
        ]);

        $token = app(JwtManagerInterface::class)->issueToken($user);

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/__test/jwt-protected')
            ->assertOk()
            ->assertJsonPath('email', 'jwt.user@example.com');
    }

    public function test_jwt_guard_denies_access_with_invalid_token(): void
    {
        Route::get('/__test/jwt-protected-invalid', static fn () => response()->json(['ok' => true]))
            ->middleware(AuthMiddleware::fromGuards(['jwt']));

        $this->withHeaders([
            'Authorization' => 'Bearer invalid.jwt.token',
        ])->getJson('/__test/jwt-protected-invalid')
            ->assertStatus(401);
    }

    public function test_jwt_guard_denies_access_with_expired_token(): void
    {
        Route::get('/__test/jwt-protected-expired', static fn () => response()->json(['ok' => true]))
            ->middleware(AuthMiddleware::fromGuards(['jwt']));

        $user = User::factory()->create();
        $token = app(JwtManagerInterface::class)->issueToken($user, [
            'iat' => time() - 120,
            'nbf' => time() - 120,
            'exp' => time() - 60,
        ]);

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/__test/jwt-protected-expired')
            ->assertStatus(401);
    }
}
