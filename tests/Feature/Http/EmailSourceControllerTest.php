<?php

declare(strict_types=1);

namespace Feature\Http;

use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailSourceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_404_when_email_does_not_exist(): void
    {
        $this->get('/email/9999/source')->assertNotFound();
    }

    public function test_returns_raw_source_as_plain_text(): void
    {
        Email::factory()->create(['number' => 42, 'source' => "Subject: hi\r\n\r\nhello"]);

        $response = $this->get('/email/42/source');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->assertSame("Subject: hi\r\n\r\nhello", $response->getContent());
    }
}
