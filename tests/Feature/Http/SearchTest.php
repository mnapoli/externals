<?php

declare(strict_types=1);

use App\Services\Search\EmailSearch;
use App\Services\Search\SearchHit;
use App\Services\Search\SearchResults;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Records the searches and returns 2 pages of results.
 */
class FakeEmailSearch implements EmailSearch
{
    /** @var list<array{string, int, ?DateTimeInterface, ?DateTimeInterface}> */
    public array $searches = [];

    public function search(string $query, int $page = 1, ?DateTimeInterface $since = null, ?DateTimeInterface $until = null): SearchResults
    {
        $this->searches[] = [$query, $page, $since, $until];
        $hit = new SearchHit(
            number: 101,
            threadNumber: 100,
            subject: '[RFC] Something <new>',
            author: 'Alice',
            date: CarbonImmutable::parse('2026-01-02 10:00:00'),
            snippet: 'the ' . SearchHit::HIGHLIGHT_START . 'enum' . SearchHit::HIGHLIGHT_END . ' <proposal>',
        );

        return new SearchResults($query, [$hit], totalHits: 21, page: $page, pageCount: 2);
    }
}

beforeEach(function (): void {
    $this->search = new FakeEmailSearch;
    $this->app->instance(EmailSearch::class, $this->search);
});

test('the search page works without a query', function (): void {
    $response = $this->get('/search');

    $response->assertOk();
    $response->assertHeader('X-Robots-Tag', 'noindex');
    $response->assertSee('name="q"', false);
    expect($this->search->searches)->toBeEmpty();
});

test('the search page lists results', function (): void {
    $response = $this->get('/search?q=enum');

    $response->assertOk();
    $response->assertSee('<a class="underline" href="/message/100#101">[RFC] Something &lt;new&gt;</a>', false);
    $response->assertSee('the <mark>enum</mark> &lt;proposal&gt;', false);
    $response->assertSee('href="' . url('/search?q=enum&amp;page=2') . '" rel="next"', false);
    $response->assertSee('<link rel="alternate" type="text/markdown" href="' . url('/search.md?q=enum') . '"', false);
});

test('search filters are passed to the search engine', function (): void {
    $this->get('/search?q=enum&page=2&since=2025-01-01&until=2025-12-31')->assertOk();

    [$query, $page, $since, $until] = $this->search->searches[0];
    expect($query)->toBe('enum')
        ->and($page)->toBe(2)
        ->and($since?->format(DATE_ATOM))->toBe('2025-01-01T00:00:00+00:00')
        ->and($until?->format(DATE_ATOM))->toBe('2025-12-31T23:59:59+00:00');
});

test('search results as markdown', function (): void {
    $response = $this->get('/search.md?q=enum');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
    expect($response->getContent())
        ->toStartWith("# Search results for \"enum\"\n")
        ->toContain('More for agents: search with ' . url('/search.md'))
        ->toContain('1. [[RFC] Something <new>](' . url('/message/101.md') . ') — email #101 in thread #100, by Alice on 2026-01-02')
        ->toContain('   > the **enum** <proposal>')
        ->toContain('Next page: ' . url('/search.md?q=enum&page=2'));
    expect($response->headers->getCookies())->toBeEmpty();
});

test('markdown search without a query explains how to search', function (): void {
    $response = $this->get('/search.md');

    $response->assertOk();
    expect($response->getContent())->toContain('/search.md?q=')->toContain('JSON API at ' . url('/api'));
});

test('markdown search rejects invalid parameters', function (): void {
    $this->get('/search.md?q=enum&page=abc')->assertUnprocessable()->assertJsonValidationErrors('page');
});

test('search results as json', function (): void {
    $response = $this->get('/api/search?q=enum');

    $response->assertOk();
    $response->assertJsonPath('meta', ['query' => 'enum', 'page' => 1, 'perPage' => 20, 'pageCount' => 2, 'totalHits' => 21]);
    $response->assertJsonPath('links', ['prev' => null, 'next' => url('/api/search?q=enum&page=2')]);
    $response->assertJsonPath('data.0', [
        'number' => 101,
        'threadNumber' => 100,
        'subject' => '[RFC] Something <new>',
        'author' => 'Alice',
        'date' => '2026-01-02T10:00:00+00:00',
        'snippet' => 'the enum <proposal>',
        'url' => url('/message/100#101'),
        'markdownUrl' => url('/message/101.md'),
        'apiUrl' => url('/api/threads/101'),
    ]);
});

test('json search requires a query', function (): void {
    $this->get('/api/search')->assertUnprocessable()->assertJsonValidationErrors('q');
});

test('the home page search falls back to the search page without javascript', function (): void {
    $this->get('/')->assertSee('<form action="/search" method="get"', false);
});
