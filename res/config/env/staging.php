<?php
declare(strict_types = 1);

use function DI\create;
use Externals\Search\ReadOnlySearchIndex;
use Externals\Search\SearchIndex;

return [

    'sentry.url' => null,

    // Assets will not be proxied by Cloudfront, instead we use the direct S3 URL
    'assetsBaseUrl' => 'https://' . $_SERVER['ASSETS_BUCKET_DOMAIN'],

    // Disable indexing in Algolia
    SearchIndex::class => create(ReadOnlySearchIndex::class),

];
