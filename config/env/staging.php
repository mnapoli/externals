<?php
declare(strict_types = 1);

use function DI\create;
use Externals\Search\ReadOnlySearchIndex;
use Externals\Search\SearchIndex;

return [

    'google.noindex' => true,
    'sentry.url' => null,

    // Assets will not be proxied by Cloudfront, instead we use the direct S3 URL
    'assetsBaseUrl' => 'https://' . $_SERVER['BUCKET_ASSETS'] . '.s3-eu-west-1.amazonaws.com',

    // Disable indexing in Algolia
    SearchIndex::class => create(ReadOnlySearchIndex::class),

];
