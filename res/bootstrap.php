<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use DI\Definition\Source\SourceCache;
use Stratify\Framework\Application;

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
    $dotenv->load();
}

// The environment of the app
$environment = getenv('LAMBDA_TASK_ROOT') ? 'prod' : 'dev';

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
        if (SourceCache::isSupported()) {
            $containerBuilder->enableDefinitionCache();
        }
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
