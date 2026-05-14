<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'githubId' => 'gh-'.fake()->unique()->numberBetween(1, 999_999),
            'name' => fake()->userName(),
        ];
    }
}
