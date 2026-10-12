<?php

declare(strict_types=1);

namespace App\Services\Search;

use DateTimeInterface;

interface EmailSearch
{
    /**
     * @param  int  $page  1-based
     */
    public function search(string $query, int $page = 1, ?DateTimeInterface $since = null, ?DateTimeInterface $until = null): SearchResults;
}
