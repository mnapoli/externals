<?php
declare(strict_types = 1);

use function DI\object;
use Externals\Search\ReadOnlySearchIndex;
use Externals\Search\SearchIndex;

return [

    // Disable indexing in Algolia
    SearchIndex::class => object(ReadOnlySearchIndex::class),

];
