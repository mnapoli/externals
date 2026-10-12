<?php

declare(strict_types=1);

namespace App\Services\Search;

class SearchResults
{
    public const int PER_PAGE = 20;

    /**
     * @param  SearchHit[]  $hits
     */
    public function __construct(
        public readonly string $query,
        public readonly array $hits,
        public readonly int $totalHits,
        public readonly int $page,
        public readonly int $pageCount,
    ) {}
}
