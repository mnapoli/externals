<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('returns rss xml', function (): void {
    $response = $this->get('/rss-rfc');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/xml; charset=utf-8');
    $this->assertStringContainsString('<rss', $response->getContent());
});
