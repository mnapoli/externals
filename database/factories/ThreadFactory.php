<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Thread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Thread>
 */
class ThreadFactory extends Factory
{
    protected $model = Thread::class;

    public function definition(): array
    {
        return [
            'emailId' => '<'.fake()->uuid().'@example.com>',
            'emailNumber' => fake()->unique()->numberBetween(1, 999_999_999),
            'lastUpdate' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),
            'emailCount' => 1,
            'votes' => 0,
        ];
    }
}
