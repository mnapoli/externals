<?php
declare(strict_types = 1);

use function DI\create;
use Externals\Search\ReadOnlySearchIndex;
use Externals\Search\SearchIndex;

return [

    'sentry.url' => null,

    // Disable indexing in Algolia
    SearchIndex::class => create(ReadOnlySearchIndex::class),

];
