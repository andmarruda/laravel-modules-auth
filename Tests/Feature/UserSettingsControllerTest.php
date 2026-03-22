<?php

namespace Andmarruda\AuthModule\Tests\Feature;

use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_authenticated_user_settings(): void
    {
        $user = User::factory()->create([
            'theme_mode' => 'dark',
        ]);
        $user->setPreference('theme_mobile_mode', 'light');
        $user->setPreference('monthly_salary', '12345.67');

        $this->actingAs($user, 'web')
            ->getJson('/api/v1/user-settings')
            ->assertOk()
            ->assertJsonPath('data.theme_mode', 'dark')
            ->assertJsonPath('data.theme_mobile_mode', 'light')
            ->assertJsonPath('data.monthly_salary', '12345.67');
    }

    public function test_can_update_authenticated_user_settings(): void
    {
        $user = User::factory()->create([
            'theme_mode' => 'light',
        ]);

        $this->actingAs($user, 'web')
            ->putJson('/api/v1/user-settings', [
                'theme_mode' => 'dark',
                'theme_mobile_mode' => 'system',
                'monthly_salary' => 9999.50,
            ])->assertOk()
            ->assertJsonPath('data.theme_mode', 'dark')
            ->assertJsonPath('data.theme_mobile_mode', 'system');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'theme_mode' => 'dark',
        ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'key' => 'theme_mobile_mode',
            'value' => 'system',
        ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'key' => 'monthly_salary',
            'value' => '9999.5',
        ]);
    }
}
