<?php
declare(strict_types=1);

use DI\ContainerBuilder;
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

// The environment of the app
$branch = getenv('PLATFORM_BRANCH');
if ($branch) {
    // If we are on platform.sh, master is prod, the rest are staging
    $environment = ($branch === 'master') ? 'prod' : 'staging';
} else {
    // Else we use the ENV variable, fallback to prod
    $environment = getenv('ENV') ?: 'prod';
}

// Create the application
$application = new class($environment) extends Application
{
    public function __construct(string $environment)
    {
        $modules = [
            'stratify/error-handler-module',
            'stratify/twig-module',
            'mnapoli/externals',
        ];
        $httpStack = require(__DIR__ . '/http.php');

        parent::__construct($modules, $environment, $httpStack);
    }

    protected function configureContainerBuilder(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->enableDefinitionCache();
        if ($this->getEnvironment() !== 'dev') {
            $containerBuilder->enableCompilation(__DIR__ . '/../var/cache/' . $this->getEnvironment());
        }
    }
};

$sentryUrl = $application->getContainer()->get('sentry.url');
if ($sentryUrl) {
    $sentry = new Raven_Client($sentryUrl);
    $sentry->install();
}

return $application;
