<?php

declare(strict_types=1);

namespace Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RssRfcControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_rss_xml(): void
    {
        $response = $this->get('/rss-rfc');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xml; charset=utf-8');
        $this->assertStringContainsString('<rss', $response->getContent());
    }
}
