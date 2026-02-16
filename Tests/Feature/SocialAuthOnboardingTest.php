<?php

namespace Andmarruda\AuthModule\Tests\Feature;

use Andmarruda\AuthModule\Models\SocialAccount;
use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Mockery;

class SocialAuthOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('authmodule.social.redirect_after_login', '/dashboard');
        config()->set('authmodule.profile.redirect_to_onboarding', '/complete-profile');
        config()->set('authmodule.profile.required_user_fields', ['name']);
        config()->set('authmodule.profile.required_preference_keys', ['phone']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_social_callback_redirects_to_onboarding_when_profile_is_incomplete(): void
    {
        $this->mockSocialUser('provider-user-1', 'social@example.com', 'Social User');

        $response = $this->get('/auth/social/google/callback');

        $response->assertRedirect('/complete-profile');

        $user = User::query()->where('email', 'social@example.com')->first();

        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->profile_completed_at);

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'provider-user-1',
        ]);
    }

    public function test_authenticated_user_can_complete_profile_and_mark_it_as_complete(): void
    {
        $user = User::factory()->create([
            'name' => 'Social User',
            'profile_completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/auth/social/profile/complete', [
                'preferences' => [
                    'phone' => '+5511999999999',
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.is_complete', true)
            ->assertJsonPath('data.missing_fields.user_fields', [])
            ->assertJsonPath('data.missing_fields.preference_keys', []);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'key' => 'phone',
            'value' => '+5511999999999',
        ]);

        $user->refresh();
        $this->assertNotNull($user->profile_completed_at);
    }

    public function test_profile_status_returns_missing_fields_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Social User',
            'profile_completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/auth/social/profile/status');

        $response->assertOk()
            ->assertJsonPath('data.is_complete', false)
            ->assertJsonPath('data.missing_fields.user_fields', [])
            ->assertJsonPath('data.missing_fields.preference_keys', ['phone']);
    }

    public function test_social_callback_redirects_to_dashboard_when_profile_is_already_complete(): void
    {
        $user = User::factory()->create([
            'name' => 'Social User',
            'email' => 'social@example.com',
            'profile_completed_at' => null,
        ]);
        $user->setPreference('phone', '+5511888888888');

        SocialAccount::query()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'provider-user-2',
            'provider_email' => 'social@example.com',
            'provider_name' => 'Social User',
        ]);

        $this->mockSocialUser('provider-user-2', 'social@example.com', 'Social User');

        $response = $this->get('/auth/social/google/callback');

        $response->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertNotNull($user->profile_completed_at);
    }

    private function mockSocialUser(string $id, string $email, string $name): void
    {
        $socialUser = Mockery::mock(SocialiteUserContract::class);
        $socialUser->shouldReceive('getId')->andReturn($id);
        $socialUser->shouldReceive('getEmail')->andReturn($email);
        $socialUser->shouldReceive('getName')->andReturn($name);
        $socialUser->shouldReceive('getNickname')->andReturn(null);

        $driver = Mockery::mock();
        $driver->shouldReceive('user')->once()->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($driver);
    }
}
