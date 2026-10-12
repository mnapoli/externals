<?php

declare(strict_types=1);

use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function emailSource(string $body): string
{
    return "From: Someone <someone@example.com>\r\nSubject: Test\r\nContent-Type: text/plain; charset=utf-8\r\n\r\n" . $body;
}

test('renders the whole thread as markdown', function (): void {
    $root = Email::factory()->create([
        'number' => 100,
        'subject' => '[RFC] Something',
        'fromName' => 'Alice',
        'date' => '2026-01-01 10:00:00',
        'source' => emailSource("Hello internals,\r\n\r\nPlease review.\r\n"),
    ]);
    Email::factory()->replyTo($root)->create([
        'number' => 101,
        'fromName' => 'Bob',
        'date' => '2026-01-02 10:00:00',
        'source' => emailSource("> Please review.\r\n>> Older quote\r\n>> Another line\r\n\r\nLooks good.\r\n"),
    ]);

    $response = $this->get('/message/100.md');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
    $markdown = $response->getContent();
    expect($markdown)
        ->toStartWith("# [RFC] Something\n")
        ->toContain('- Messages: 2')
        ->toContain('https://news-web.php.net/php.internals/100')
        ->toContain('## #100 — Alice — 2026-01-01T10:00:00+00:00')
        ->toContain('Please review.')
        ->toContain('## #101 — Bob — 2026-01-02T10:00:00+00:00')
        ->toContain('/message/100#101 · In reply to: #100')
        ->toContain("> Please review.\n> > […]\n\nLooks good.")
        ->not->toContain('Older quote')
        ->not->toContain('<p>');
});

test('does not start a session', function (): void {
    Email::factory()->create(['number' => 100]);

    $response = $this->get('/message/100.md');

    $response->assertOk();
    expect($response->headers->getCookies())->toBeEmpty();
});

test('redirects replies to the markdown of the thread root', function (): void {
    $root = Email::factory()->create(['number' => 100]);
    Email::factory()->replyTo($root)->create(['number' => 101]);

    $this->get('/message/101.md')->assertRedirect('/message/100.md');
});

test('returns 404 when the email does not exist', function (): void {
    $this->get('/message/12345.md')->assertNotFound();
});

test('serves markdown on the thread URL when the client prefers it', function (): void {
    Email::factory()->create(['number' => 100, 'subject' => 'Hello world']);

    $response = $this->get('/message/100', ['Accept' => 'text/markdown, text/html;q=0.9']);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
    expect($response->getContent())->toStartWith("# Hello world\n");
});

test('serves html on the thread URL to browsers', function (): void {
    Email::factory()->create(['number' => 100, 'subject' => 'Hello world']);

    $response = $this->get('/message/100', ['Accept' => 'text/html,application/xhtml+xml,*/*;q=0.8']);

    $response->assertOk();
    $response->assertHeader('Vary', 'Accept');
    $response->assertSee('<link rel="alternate" type="text/markdown" href="/message/100.md"', false);
});
