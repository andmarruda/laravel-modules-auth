<?php

namespace Andmarruda\AuthModule\Factories;

use Andmarruda\AuthModule\Models\Team;
use Andmarruda\AuthModule\Models\TeamInvitation;
use Andmarruda\AuthModule\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Andmarruda\AuthModule\Models\TeamInvitation>
 */
class TeamInvitationFactory extends Factory
{
    protected $model = TeamInvitation::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'email' => fake()->safeEmail(),
            'token' => Str::random(64),
            'inviter_id' => User::factory(),
            'inviter_type' => User::class,
            'role' => 'member',
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => [
            'accepted_at' => now(),
        ]);
    }
}
