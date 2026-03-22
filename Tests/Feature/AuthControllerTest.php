<?php

namespace Andmarruda\AuthModule\Tests\Feature;

use Andmarruda\AuthModule\Infrastructure\Mail\PasswordResetOtpMail;
use Andmarruda\AuthModule\Models\Invitation;
use Andmarruda\AuthModule\Models\Otp;
use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Ports\Services\JwtManagerInterface;
use Andmarruda\AuthModule\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_can_login_with_jwt_channel_using_canonical_endpoint(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'SecurePass123!',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'SecurePass123!',
            'channel' => 'jwt',
        ]);

        $response->assertOk()
            ->assertJsonPath('type', 'Bearer')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('email', 'login@example.com');

        $payload = app(JwtManagerInterface::class)->decode((string) $response->json('token'));
        $this->assertSame((string) $user->id, $payload['sub'] ?? null);
    }

    public function test_can_return_authenticated_user_payload_for_jwt_requests(): void
    {
        $user = User::factory()->create([
            'email' => 'jwt-me@example.com',
            'theme_mode' => 'dark',
        ]);
        $user->setPreference('theme_mobile_mode', 'light');

        $token = app(JwtManagerInterface::class)->issueToken($user);

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('user.settings.theme_mobile_mode', 'light')
            ->assertJsonPath('user.theme_mode', 'dark');
    }

    public function test_can_refresh_jwt_token_through_canonical_endpoint(): void
    {
        $user = User::factory()->create();
        $token = app(JwtManagerInterface::class)->issueToken($user, [
            'iat' => time() - 10,
            'nbf' => time() - 10,
            'exp' => time() + 10,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/v1/auth/refresh');

        $response->assertOk()
            ->assertJsonPath('type', 'Bearer')
            ->assertJsonPath('user.id', $user->id);

        $this->assertNotSame($token, $response->json('token'));
    }

    public function test_can_logout_web_session_through_canonical_endpoint(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_can_register_open_user_through_canonical_endpoint(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Open User',
            'email' => 'open@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'channel' => 'jwt',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('email', 'open@example.com')
            ->assertJsonPath('user.email', 'open@example.com')
            ->assertJsonPath('type', 'Bearer');

        $this->assertDatabaseHas('users', [
            'email' => 'open@example.com',
            'name' => 'Open User',
        ]);
    }

    public function test_can_register_with_invitation_through_canonical_endpoint(): void
    {
        $invitation = Invitation::factory()->create([
            'email' => 'invite-auth@example.com',
        ]);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Invited User',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'invitation_token' => $invitation->token,
            'channel' => 'jwt',
        ])->assertStatus(201)
            ->assertJsonPath('email', 'invite-auth@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'invite-auth@example.com',
            'name' => 'Invited User',
        ]);
    }

    public function test_can_request_password_reset_code(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        $this->postJson('/api/v1/auth/password/reset', [
            'email' => 'reset@example.com',
        ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('otps', [
            'user_id' => $user->id,
            'type' => 'password_reset',
        ]);

        Mail::assertSent(PasswordResetOtpMail::class);
    }

    public function test_can_reset_password_with_code(): void
    {
        $user = User::factory()->create([
            'email' => 'finish-reset@example.com',
            'password' => 'OldPass123!',
        ]);

        Otp::query()->create([
            'user_id' => $user->id,
            'code' => '123456',
            'type' => 'password_reset',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/v1/auth/password/reset', [
            'email' => 'finish-reset@example.com',
            'code' => '123456',
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(auth()->getProvider()->validateCredentials(
            $user->fresh(),
            ['password' => 'NewPass123!'],
        ));
    }
}
