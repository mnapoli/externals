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
        ->toContain('- More for agents: search with ' . url('/search.md') . '?q={query} · RFCs at ' . url('/rfc.md') . ' · JSON API at ' . url('/api') . ' · documentation at ' . url('/llms.txt'))
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

/**
 * A thread of 30 emails, numbered 100 to 129 in chronological order.
 */
function longThread(): void
{
    $root = Email::factory()->create(['number' => 100, 'date' => '2026-01-01 00:00:00']);
    for ($i = 1; $i < 30; $i++) {
        Email::factory()->replyTo($root)->create(['number' => 100 + $i, 'date' => "2026-01-01 00:$i:00"]);
    }
}

test('paginates long threads', function (): void {
    longThread();

    $first = $this->get('/message/100.md')->assertOk()->getContent();
    expect($first)
        ->toContain('- Messages: 30')
        ->toContain('- Page 1 of 2: messages 1 to 25 (25 per page, add `?page=N` to the URL)')
        ->toContain('- Next page: ' . url('/message/100.md?page=2'))
        ->toContain('## #124 ')
        ->not->toContain('## #125 ')
        ->toEndWith('Next page: ' . url('/message/100.md?page=2') . "\n");

    $second = $this->get('/message/100.md?page=2')->assertOk()->getContent();
    expect($second)
        ->toContain('- Page 2 of 2: messages 26 to 30')
        ->toContain('## #125 ')
        ->toContain('## #129 ')
        ->not->toContain('## #124 ')
        ->not->toContain('Next page');
});

test('short threads have no pagination', function (): void {
    Email::factory()->create(['number' => 100]);

    expect($this->get('/message/100.md')->getContent())->not->toContain('Page 1 of');
});

test('redirects replies to the page that contains them', function (): void {
    longThread();

    $this->get('/message/110.md')->assertRedirect('/message/100.md');
    $this->get('/message/127.md')->assertRedirect('/message/100.md?page=2');
});

test('returns 404 for pages that do not exist', function (string $page): void {
    longThread();

    $this->get("/message/100.md?page=$page")->assertNotFound();
})->with(['0', '3', 'abc']);

test('paginates the markdown served on the thread URL', function (): void {
    longThread();

    $response = $this->get('/message/100?page=2', ['Accept' => 'text/markdown']);

    expect($response->getContent())->toContain('- Page 2 of 2');
});
