<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
    $dotenv->load();
}

// Platform.sh config
$relationships = getenv('PLATFORM_RELATIONSHIPS');
if ($relationships) {
    $relationships = json_decode(base64_decode($relationships), true);

    foreach ($relationships['database'] as $endpoint) {
        if (empty($endpoint['query']['is_master'])) {
            continue;
        }
        $dbUrl = sprintf(
            'mysql://%s:%s@%s:%s/%s',
            $endpoint['username'],
            $endpoint['password'],
            $endpoint['host'],
            $endpoint['port'],
            $endpoint['path']
        );
        putenv("DB_URL=$dbUrl");
    }
}
