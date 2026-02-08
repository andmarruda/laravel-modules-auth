<?php

namespace App\Modules\AuthModule\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Modules\AuthModule\Models\Invitation;
use App\Modules\AuthModule\Models\User;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_with_valid_invitation(): void
    {
        $invitation = Invitation::factory()->create([
            'email' => 'newuser@example.com',
        ]);

        $response = $this->postJson('/users/register', [
            'token' => $invitation->token,
            'name' => 'New User',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
        ]);

        $this->assertDatabaseHas('invitations', [
            'id' => $invitation->id,
            'accepted_at' => now(),
        ]);
    }

    public function test_cannot_register_without_invitation(): void
    {
        $response = $this->postJson('/users/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    public function test_cannot_register_with_invalid_token(): void
    {
        $response = $this->postJson('/users/register', [
            'token' => 'invalid-token-12345',
            'name' => 'New User',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(404);
    }

    public function test_cannot_register_with_expired_invitation(): void
    {
        $invitation = Invitation::factory()->expired()->create();

        $response = $this->postJson('/users/register', [
            'token' => $invitation->token,
            'name' => 'New User',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(410);
    }

    public function test_cannot_register_with_already_used_invitation(): void
    {
        $invitation = Invitation::factory()->accepted()->create();

        $response = $this->postJson('/users/register', [
            'token' => $invitation->token,
            'name' => 'New User',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(410);
    }

    public function test_register_requires_password_confirmation(): void
    {
        $invitation = Invitation::factory()->create();

        $response = $this->postJson('/users/register', [
            'token' => $invitation->token,
            'name' => 'New User',
            'password' => 'SecurePass123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_creates_audit_log(): void
    {
        $invitation = Invitation::factory()->create([
            'email' => 'newuser@example.com',
        ]);

        $this->postJson('/users/register', [
            'token' => $invitation->token,
            'name' => 'New User',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $this->assertDatabaseHas('auth_audit_logs', [
            'action' => 'user_registered',
            'target_email' => 'newuser@example.com',
            'invitation_id' => $invitation->id,
        ]);
    }
}
