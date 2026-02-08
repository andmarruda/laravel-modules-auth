<?php

namespace App\Modules\AuthModule\Tests\Feature;

use App\Modules\AuthModule\Infrastructure\Mail\InvitationMail;
use App\Modules\AuthModule\Models\Invitation;
use App\Modules\AuthModule\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvitationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_manager_can_invite_user(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager)
            ->postJson('/invitations/create', [
                'email' => 'newuser@example.com',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'email', 'expires_at'],
            ]);

        $this->assertDatabaseHas('invitations', [
            'email' => 'newuser@example.com',
            'invited_by' => $manager->id,
        ]);

        Mail::assertQueued(InvitationMail::class, function ($mail) {
            return $mail->invitation->email === 'newuser@example.com';
        });
    }

    public function test_manager_can_invite_user_with_resource_scope(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager)
            ->postJson('/invitations/create', [
                'email' => 'newuser@example.com',
                'resource_scope' => 'project:123',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.resource_scope', 'project:123');

        $this->assertDatabaseHas('invitations', [
            'email' => 'newuser@example.com',
            'resource_scope' => 'project:123',
        ]);
    }

    public function test_non_manager_cannot_invite_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/invitations/create', [
                'email' => 'newuser@example.com',
            ]);

        $response->assertStatus(403);
        Mail::assertNothingQueued();
    }

    public function test_invite_requires_valid_email(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager)
            ->postJson('/invitations/create', [
                'email' => 'invalid-email',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_cannot_invite_already_registered_email(): void
    {
        $manager = User::factory()->manager()->create();
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($manager)
            ->postJson('/invitations/create', [
                'email' => 'existing@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_accept_invitation_with_valid_token(): void
    {
        $invitation = Invitation::factory()->create();

        $response = $this->postJson('/invitations/accept', [
            'token' => $invitation->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['email', 'accepted_at'],
            ]);

        $this->assertDatabaseHas('invitations', [
            'id' => $invitation->id,
        ]);

        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
    }

    public function test_cannot_accept_expired_invitation(): void
    {
        $invitation = Invitation::factory()->expired()->create();

        $response = $this->postJson('/invitations/accept', [
            'token' => $invitation->token,
        ]);

        $response->assertStatus(410);
    }

    public function test_accept_already_used_invitation_is_idempotent(): void
    {
        $invitation = Invitation::factory()->accepted()->create();

        $response = $this->postJson('/invitations/accept', [
            'token' => $invitation->token,
        ]);

        // Idempotent: returns success even if already accepted
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['email', 'accepted_at'],
            ]);
    }

    public function test_cannot_accept_invalid_token(): void
    {
        $response = $this->postJson('/invitations/accept', [
            'token' => 'invalid-token-12345',
        ]);

        $response->assertStatus(404);
    }

    public function test_guest_cannot_create_invitation(): void
    {
        $response = $this->postJson('/invitations/create', [
            'email' => 'newuser@example.com',
        ]);

        $response->assertStatus(401);
    }

    public function test_invitation_creates_audit_log(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->postJson('/invitations/create', [
                'email' => 'newuser@example.com',
            ]);

        $this->assertDatabaseHas('auth_audit_logs', [
            'action' => 'invitation_created',
            'actor_id' => $manager->id,
            'target_email' => 'newuser@example.com',
        ]);
    }

    public function test_accept_invitation_creates_audit_log(): void
    {
        $invitation = Invitation::factory()->create();

        $this->postJson('/invitations/accept', [
            'token' => $invitation->token,
        ]);

        $this->assertDatabaseHas('auth_audit_logs', [
            'action' => 'invitation_accepted',
            'invitation_id' => $invitation->id,
        ]);
    }
}
