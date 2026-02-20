<?php

namespace Andmarruda\AuthModule\Factories;

use Andmarruda\AuthModule\Models\Team;
use Andmarruda\AuthModule\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Andmarruda\AuthModule\Models\Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => Str::slug(fake()->unique()->company()) . '-' . Str::lower(Str::random(6)),
            'owner_id' => User::factory(),
        ];
    }
}
