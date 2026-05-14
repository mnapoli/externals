<?php

declare(strict_types=1);

namespace Feature\Http;

use App\Models\Email;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_stats_with_counts(): void
    {
        User::factory()->count(3)->create();
        $root = Email::factory()->create();
        Email::factory()->replyTo($root)->create();
        Email::factory()->replyTo($root)->create();

        $response = $this->get('/stats');

        $response->assertOk();
        $response->assertViewIs('stats');
        $response->assertViewHas('userCount', 3);
        $response->assertViewHas('threadCount', 1);
        $response->assertViewHas('emailCount', 3);
    }
}
