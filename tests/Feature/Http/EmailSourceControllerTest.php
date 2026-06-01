<?php

declare(strict_types=1);

namespace Feature\Http;

use App\Models\Email;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailSourceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirects_to_login_when_unauthenticated(): void
    {
        $this->get('/email/42/source')->assertRedirect('/login');
    }

    public function test_returns_404_when_email_does_not_exist(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/email/9999/source')
            ->assertNotFound();
    }

    public function test_returns_raw_source_as_plain_text(): void
    {
        Email::factory()->create(['number' => 42, 'source' => "Subject: hi\r\n\r\nhello"]);

        $response = $this->actingAs(User::factory()->create())
            ->get('/email/42/source');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->assertSame("Subject: hi\r\n\r\nhello", $response->getContent());
    }
}
