<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Rfc;
use App\Services\Rfc\RfcStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rfc>
 */
class RfcFactory extends Factory
{
    protected $model = Rfc::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(3);

        return [
            'slug' => $slug,
            'title' => ucfirst(str_replace('-', ' ', $slug)),
            'status' => RfcStatus::Discussion,
            'indexPosition' => fake()->unique()->numberBetween(0, 100_000),
            'isListed' => true,
            'version' => '1.0',
            'date' => '2026-01-01',
            'authors' => fake()->name(),
            'rawStatus' => 'Under Discussion',
            'content' => "## Introduction\n\nThis RFC proposes something.",
            'fetchedAt' => now(),
        ];
    }
}
