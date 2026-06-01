<?php

declare(strict_types=1);

namespace Feature\Http;

use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RssControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_rss_xml(): void
    {
        Email::factory()->create(['number' => 1, 'subject' => 'First']);
        Email::factory()->create(['number' => 2, 'subject' => 'Second']);

        $response = $this->get('/rss');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xml; charset=utf-8');
        $this->assertStringContainsString('<rss', $response->getContent());
        $this->assertStringContainsString('First', $response->getContent());
        $this->assertStringContainsString('Second', $response->getContent());
    }

    public function test_since_query_filters_older_emails(): void
    {
        Email::factory()->create(['number' => 1, 'subject' => 'Old']);
        Email::factory()->create(['number' => 5, 'subject' => 'New']);

        $response = $this->get('/rss?since=2');

        $response->assertOk();
        $this->assertStringNotContainsString('Old', $response->getContent());
        $this->assertStringContainsString('New', $response->getContent());
    }
}
