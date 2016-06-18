<?php

namespace Externals\Application;

use DI\ContainerBuilder;
use Dotenv\Dotenv;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class Application extends \Stratify\Framework\Application
{
    public function __construct()
    {
        $modules = [
            'error-handler',
            'twig',
            'app',
        ];

        $dotenv = new Dotenv(__DIR__ . '/../../');
        $dotenv->load();
        $environment = getenv('ENV') ?? 'prod';

        parent::__construct($modules, $environment);
    }

    protected function configureContainerBuilder(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->useAnnotations(true);
        return $containerBuilder;
    }
}
