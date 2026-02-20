<?php

namespace Andmarruda\AuthModule\Tests\Feature;

use Andmarruda\AuthModule\Infrastructure\Mail\TeamInvitationMail;
use Andmarruda\AuthModule\Models\Team;
use Andmarruda\AuthModule\Models\TeamInvitation;
use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

class TeamInvitationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_authenticated_user_can_create_team(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/teams', [
            'name' => 'Core Team',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Core Team');

        $this->assertDatabaseHas('teams', [
            'name' => 'Core Team',
            'owner_id' => $user->id,
        ]);
    }

    public function test_authenticated_user_can_create_team_with_sanctum_guard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/teams', [
            'name' => 'Sanctum Team',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Sanctum Team');

        $this->assertDatabaseHas('teams', [
            'name' => 'Sanctum Team',
            'owner_id' => $user->id,
        ]);
    }

    public function test_owner_can_invite_user_to_team(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->users()->attach($owner->id, ['role' => 'owner', 'joined_at' => now()]);

        $response = $this->actingAs($owner)->postJson('/teams/invitations/create', [
            'team_id' => $team->id,
            'email' => 'invitee@example.com',
            'role' => 'member',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.team_id', $team->id)
            ->assertJsonPath('data.inviter_type', User::class)
            ->assertJsonPath('data.inviter_id', $owner->id);

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $team->id,
            'email' => 'invitee@example.com',
            'role' => 'member',
        ]);

        Mail::assertQueued(TeamInvitationMail::class);
    }

    public function test_cannot_spoof_another_user_as_inviter_context(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->users()->attach($owner->id, ['role' => 'owner', 'joined_at' => now()]);

        $response = $this->actingAs($owner)->postJson('/teams/invitations/create', [
            'team_id' => $team->id,
            'email' => 'invitee@example.com',
            'role' => 'member',
            'inviter_type' => 'user',
            'inviter_id' => $otherUser->id,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Forbidden inviter context.');
    }

    public function test_resolve_team_invitation_indicates_existing_account(): void
    {
        $team = Team::factory()->create();
        $inviter = User::factory()->create();
        $user = User::factory()->create(['email' => 'existing@example.com']);
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'inviter_type' => User::class,
            'inviter_id' => $inviter->id,
            'email' => $user->email,
        ]);

        $response = $this->getJson('/teams/invitations/resolve?token=' . $invitation->token);

        $response->assertOk()
            ->assertJsonPath('data.has_account', true)
            ->assertJsonPath('data.email', 'existing@example.com');
    }

    public function test_guest_redeem_requires_authentication_or_registration(): void
    {
        $invitation = TeamInvitation::factory()->create();

        $response = $this->postJson('/teams/invitations/redeem', [
            'token' => $invitation->token,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'requires_authentication');
    }

    public function test_existing_user_can_redeem_team_invitation(): void
    {
        $user = User::factory()->create(['email' => 'member@example.com']);
        $team = Team::factory()->create();
        $inviter = User::factory()->create();
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'inviter_type' => User::class,
            'inviter_id' => $inviter->id,
            'email' => $user->email,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->postJson('/teams/invitations/redeem', [
            'token' => $invitation->token,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'joined')
            ->assertJsonPath('data.role', 'admin');

        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);
    }

    public function test_guest_can_register_from_team_invitation(): void
    {
        $team = Team::factory()->create();
        $inviter = User::factory()->create();
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'inviter_type' => User::class,
            'inviter_id' => $inviter->id,
            'email' => 'newmember@example.com',
        ]);

        $response = $this->postJson('/teams/invitations/register', [
            'token' => $invitation->token,
            'name' => 'New Member',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.email', 'newmember@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'newmember@example.com',
            'name' => 'New Member',
        ]);
    }
}
