<?php

declare(strict_types=1);

use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('returns rss xml', function (): void {
    Email::factory()->create(['number' => 1, 'subject' => 'First']);
    Email::factory()->create(['number' => 2, 'subject' => 'Second']);

    $response = $this->get('/rss');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/xml; charset=utf-8');
    $this->assertStringContainsString('<rss', $response->getContent());
    $this->assertStringContainsString('First', $response->getContent());
    $this->assertStringContainsString('Second', $response->getContent());
});

test('since query filters older emails', function (): void {
    Email::factory()->create(['number' => 1, 'subject' => 'Old']);
    Email::factory()->create(['number' => 5, 'subject' => 'New']);

    $response = $this->get('/rss?since=2');

    $response->assertOk();
    $this->assertStringNotContainsString('Old', $response->getContent());
    $this->assertStringContainsString('New', $response->getContent());
});
