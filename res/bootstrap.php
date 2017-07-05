<?php
declare(strict_types=1);

use Stratify\Framework\Application;

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
    $dotenv->load();
}

// Platform.sh DB config
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

$modules = [
    'stratify/error-handler-module',
    'stratify/twig-module',
    'mnapoli/externals',
];
$environment = getenv('ENV') ?: 'prod';
$httpStack = require(__DIR__ . '/http.php');
$application = new Application($modules, $environment, $httpStack);

$sentryUrl = $application->getContainer()->get('sentry.url');
if ($sentryUrl) {
    $sentry = new Raven_Client($sentryUrl);
    $sentry->install();
}

return $application;
