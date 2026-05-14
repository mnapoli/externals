<?php

declare(strict_types=1);

namespace Feature\Http;

use Tests\TestCase;

class NewsControllerTest extends TestCase
{
    public function test_renders_news_page(): void
    {
        $response = $this->get('/news');

        $response->assertOk();
        $response->assertViewIs('news');
    }
}
