<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use DI\Definition\Source\SourceCache;
use Dotenv\Dotenv;

// Waiting for an update of https://github.com/lcobucci/jwt
error_reporting(E_ALL ^ E_USER_DEPRECATED);

ini_set('mysql.connect_timeout','5');

require_once __DIR__ . '/../vendor/autoload.php';

// The environment of the app
$environment = getenv('APP_ENV') ?: 'dev';

if ($environment === 'dev' && file_exists(__DIR__ . '/../.env')) {
    Dotenv::createUnsafeImmutable(__DIR__ . '/../')->load();
}

// Create the application
$containerBuilder = new ContainerBuilder;
$containerBuilder->useAttributes(true);
$containerBuilder->addDefinitions(__DIR__ . '/config.php');
$envConfig = __DIR__ . "/env/$environment.php";
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
    \Sentry\init(['dsn' => $sentryUrl]);
}

return $container;
