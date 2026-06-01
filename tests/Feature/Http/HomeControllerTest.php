<?php

declare(strict_types=1);

namespace Feature\Http;

use App\Models\Email;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_home_for_guest(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('home');
        $response->assertViewHas('user', null);
        $response->assertViewHas('page', 1);
    }

    public function test_renders_home_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertViewHas('user', fn($viewUser) => $viewUser?->is($user));
    }

    public function test_page_count_reflects_thread_root_count(): void
    {
        Email::factory()->count(25)->create();

        $response = $this->get('/');

        $response->assertOk();
        // 25 threads / 20 per page = 2 pages
        $response->assertViewHas('pageCount', 2);
    }
}
