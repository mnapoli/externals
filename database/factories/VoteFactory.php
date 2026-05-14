<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Vote;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
{
    protected $model = Vote::class;

    public function definition(): array
    {
        return [
            'userId' => 0,
            'emailNumber' => 0,
            'value' => 1,
            'updatedAt' => new DateTimeImmutable,
        ];
    }
}
