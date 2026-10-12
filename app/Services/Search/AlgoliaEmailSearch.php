<?php

declare(strict_types=1);

namespace App\Services\Search;

use Algolia\AlgoliaSearch\Api\SearchClient;
use App\Models\Email;
use Carbon\CarbonImmutable;
use DateTimeInterface;

class AlgoliaEmailSearch implements EmailSearch
{
    public function __construct(
        private readonly SearchClient $searchClient,
        private readonly string $indexPrefix,
    ) {}

    public function search(string $query, int $page = 1, ?DateTimeInterface $since = null, ?DateTimeInterface $until = null): SearchResults
    {
        // Only numeric attributes can be filtered without changing the index settings
        $filters = [];
        if ($since) {
            $filters[] = 'timestamp >= ' . $since->getTimestamp();
        }
        if ($until) {
            $filters[] = 'timestamp <= ' . $until->getTimestamp();
        }

        /** @var array{hits: list<array<string, mixed>>, nbHits: int, nbPages: int} $response */
        $response = $this->searchClient->searchSingleIndex($this->indexPrefix . 'emails', [
            'query' => $query,
            'page' => $page - 1,
            'hitsPerPage' => SearchResults::PER_PAGE,
            'filters' => implode(' AND ', $filters),
            'attributesToRetrieve' => ['subject', 'fromName', 'fromEmail', 'date', 'threadId'],
            'attributesToHighlight' => [],
            'attributesToSnippet' => ['extract:40'],
            'highlightPreTag' => SearchHit::HIGHLIGHT_START,
            'highlightPostTag' => SearchHit::HIGHLIGHT_END,
            'snippetEllipsisText' => '…',
        ]);

        // The index stores the thread root's email ID: resolve it to the number used in URLs
        $threadIds = array_filter(array_column($response['hits'], 'threadId'));
        /** @var array<string, int> $threadNumbers */
        $threadNumbers = Email::whereIn('id', $threadIds)->pluck('number', 'id')->all();

        return new SearchResults(
            query: $query,
            hits: array_map(fn(array $hit) => $this->hit($hit, $threadNumbers), $response['hits']),
            totalHits: $response['nbHits'],
            page: $page,
            pageCount: $response['nbPages'],
        );
    }

    /**
     * @param  array<string, mixed>  $hit
     * @param  array<string, int>  $threadNumbers
     */
    private function hit(array $hit, array $threadNumbers): SearchHit
    {
        /** @var string $snippet */
        $snippet = $hit['_snippetResult']['extract']['value'] ?? '';
        // The indexed extract is the stored HTML content with tags stripped
        $snippet = html_entity_decode($snippet, ENT_QUOTES | ENT_HTML5);
        $snippet = mb_trim((string) preg_replace('/\s+/u', ' ', $snippet));

        $number = (int) $hit['objectID'];

        return new SearchHit(
            number: $number,
            // The thread root may be missing: the email is then shown as its own thread
            threadNumber: $threadNumbers[$hit['threadId'] ?? ''] ?? $number,
            subject: (string) $hit['subject'],
            author: (string) (($hit['fromName'] ?? '') ?: ($hit['fromEmail'] ?? '')),
            date: CarbonImmutable::parse((string) $hit['date']),
            snippet: $snippet,
        );
    }
}
