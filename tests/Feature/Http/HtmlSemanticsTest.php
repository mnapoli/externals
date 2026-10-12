<?php

declare(strict_types=1);

use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['app.url' => 'https://externals.io']);
});

/**
 * @return array<string, mixed>
 */
function extractJsonLd(string $html): array
{
    $head = mb_strstr($html, '</head>', true);
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', (string) $head, $matches);
    expect($matches)->not->toBeEmpty();

    return json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
}

test('home page has a canonical URL', function (): void {
    $response = $this->get('/');

    $response->assertSee('<link rel="canonical" href="https://externals.io/">', false);
    $response->assertSee('<meta name="og:url" content="https://externals.io/">', false);
});

test('canonical URL of list pages keeps the page number', function (): void {
    $this->get('/top?page=3&foo=bar')
        ->assertSee('<link rel="canonical" href="https://externals.io/top?page=3">', false);

    $this->get('/top?page=1')
        ->assertSee('<link rel="canonical" href="https://externals.io/top">', false);
});

test('thread page has a canonical URL', function (): void {
    Email::factory()->create(['number' => 100]);

    $this->get('/message/100')
        ->assertSee('<link rel="canonical" href="https://externals.io/message/100">', false)
        ->assertSee('<meta name="og:url" content="https://externals.io/message/100">', false);
});

test('thread messages expose a machine-readable date', function (): void {
    Email::factory()->create(['number' => 100, 'date' => '2024-03-01 10:20:30']);

    $this->get('/message/100')
        ->assertSee('<time datetime="2024-03-01T10:20:30+00:00">', false);
});

test('thread page exposes the discussion as JSON-LD', function (): void {
    $root = Email::factory()->create([
        'number' => 100,
        'subject' => '[RFC] Pattern matching',
        'date' => '2024-03-01 10:00:00',
        'fromName' => 'Alice',
        'fromEmail' => 'alice@example.com',
    ]);
    Email::factory()->replyTo($root)->create([
        'number' => 101,
        'date' => '2024-03-02 10:00:00',
        'fromName' => 'Bob',
        'fromEmail' => 'bob@example.com',
    ]);
    Email::factory()->replyTo($root)->create([
        'number' => 102,
        'date' => '2024-03-03 10:00:00',
        'fromName' => null,
        'fromEmail' => 'anonymous@example.com',
    ]);

    $response = $this->get('/message/100');
    $jsonLd = extractJsonLd($response->getContent());

    expect($jsonLd)->toMatchArray([
        '@context' => 'https://schema.org',
        '@type' => 'DiscussionForumPosting',
        'headline' => '[RFC] Pattern matching',
        'url' => 'https://externals.io/message/100',
        'datePublished' => '2024-03-01T10:00:00+00:00',
        'author' => ['@type' => 'Person', 'name' => 'Alice'],
        'interactionStatistic' => [
            '@type' => 'InteractionCounter',
            'interactionType' => 'https://schema.org/CommentAction',
            'userInteractionCount' => 2,
        ],
    ]);
    expect($jsonLd['comment'])->toBe([
        [
            '@type' => 'Comment',
            'url' => 'https://externals.io/message/100#101',
            'datePublished' => '2024-03-02T10:00:00+00:00',
            'author' => ['@type' => 'Person', 'name' => 'Bob'],
        ],
        [
            '@type' => 'Comment',
            'url' => 'https://externals.io/message/100#102',
            'datePublished' => '2024-03-03T10:00:00+00:00',
        ],
    ]);
    expect(json_encode($jsonLd))->not->toContain('@example.com');
});
