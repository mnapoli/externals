<?php

declare(strict_types=1);

return [
    'version' => env('APP_VERSION', ''),
    'assets_base_url' => env('ASSETS_BASE_URL', ''),
    'no_index' => env('GOOGLE_NO_INDEX', false),
    'rss_host' => env('RSS_HOST', 'https://externals.io'),
    'maintenance_mode' => env('MAINTENANCE_MODE', false),
];
