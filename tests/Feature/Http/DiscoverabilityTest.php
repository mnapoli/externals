<?php

declare(strict_types=1);

use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('every page tells agents where to find the markdown and the api', function (): void {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('For AI agents:', false);
    $response->assertSee('href="/api"', false);
    $response->assertSee('href="/llms.txt"', false);
});

test('the thread page links to its markdown version', function (): void {
    Email::factory()->create(['number' => 100]);

    $this->get('/message/100')->assertSee('<a href="/message/100.md" type="text/markdown"', false);
});

test('the api index lists the endpoints', function (): void {
    $response = $this->get('/api');

    $response->assertOk();
    $response->assertJsonPath('documentation', url('/llms.txt'));
    expect(array_column($response->json('endpoints'), 'url'))->toBe([
        url('/api/threads'),
        url('/api/threads/{number}'),
        url('/api/search'),
    ]);
});

test('unknown api urls return a json 404', function (): void {
    $this->get('/api/unknown')->assertNotFound()->assertJsonStructure(['message']);
});

test('unknown pages still return the html 404 page', function (): void {
    $response = $this->get('/unknown');

    $response->assertNotFound();
    $response->assertSee('😱');
});
