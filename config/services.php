<?php

declare(strict_types=1);

return [

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI', '/login'),
    ],

    'algolia' => [
        'app_id' => env('ALGOLIA_APP_ID', ''),
        'api_key' => env('ALGOLIA_API_KEY', ''),
        'index_prefix' => env('ALGOLIA_INDEX_PREFIX', 'dev_'),
    ],

];
