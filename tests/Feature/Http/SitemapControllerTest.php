<?php

declare(strict_types=1);

use App\Models\Email;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createSitemapThread(int $number, string $lastUpdate): void
{
    $email = Email::factory()->create(['number' => $number]);
    Thread::factory()->create([
        'emailId' => $email->id,
        'emailNumber' => $number,
        'lastUpdate' => $lastUpdate,
    ]);
}

/**
 * @return list<string>
 */
function sitemapValues(string $content, string $element): array
{
    $xml = simplexml_load_string($content);
    expect($xml)->not->toBeFalse();
    $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

    return array_map('strval', $xml->xpath("//s:$element"));
}

test('sitemap index lists the pages sitemap and the threads sitemaps', function (): void {
    createSitemapThread(100, '2026-01-02 10:00:00');

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');
    expect(simplexml_load_string($response->getContent())->getName())->toBe('sitemapindex');
    expect(sitemapValues($response->getContent(), 'sitemap/s:loc'))->toBe([
        url('/sitemaps/pages.xml'),
        url('/sitemaps/threads-1.xml'),
    ]);
});

test('threads sitemap lists threads ordered by number with their last update', function (): void {
    createSitemapThread(200, '2026-03-04 10:00:00');
    createSitemapThread(100, '2026-01-02 10:00:00');

    $response = $this->get('/sitemaps/threads-1.xml');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');
    expect(sitemapValues($response->getContent(), 'url/s:loc'))->toBe([
        url('/message/100'),
        url('/message/200'),
    ]);
    expect(sitemapValues($response->getContent(), 'url/s:lastmod'))->toBe([
        '2026-01-02',
        '2026-03-04',
    ]);
});

test('threads sitemap returns 404 for out of range pages', function (): void {
    createSitemapThread(100, '2026-01-02 10:00:00');

    $this->get('/sitemaps/threads-2.xml')->assertNotFound();
    $this->get('/sitemaps/threads-0.xml')->assertNotFound();
});

test('pages sitemap lists the thread listings', function (): void {
    $response = $this->get('/sitemaps/pages.xml');

    $response->assertOk();
    expect(sitemapValues($response->getContent(), 'url/s:loc'))->toBe([url('/'), url('/top')]);
});
