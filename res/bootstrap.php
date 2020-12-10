<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use DI\Definition\Source\SourceCache;

ini_set('mysql.connect_timeout','5');

require_once __DIR__ . '/../vendor/autoload.php';

// The environment of the app
$environment = getenv('APP_ENV') ?: 'dev';

if ($environment === 'dev' && file_exists(__DIR__ . '/../.env')) {
    $dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
    $dotenv->load();
}

// Create the application
$containerBuilder = new ContainerBuilder;
$containerBuilder->useAttributes(true);
$containerBuilder->addDefinitions(__DIR__ . '/config/config.php');
$envConfig = __DIR__ . "/config/env/$environment.php";
if (file_exists($envConfig)) {
    $containerBuilder->addDefinitions($envConfig);
}

if ($environment !== 'dev' && SourceCache::isSupported()) {
    $containerBuilder->enableDefinitionCache();
}
if ($environment !== 'dev') {
    $containerBuilder->enableCompilation('/tmp/phpdi');
}
$container = $containerBuilder->build();

$sentryUrl = $container->get('sentry.url');
if ($sentryUrl) {
    $sentry = new Raven_Client($sentryUrl);
    $sentry->install();
}

return $container;
