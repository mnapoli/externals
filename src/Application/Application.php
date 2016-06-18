<?php

namespace Externals\Application;

use DI\ContainerBuilder;
use Dotenv\Dotenv;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class Application extends \Stratify\Framework\Application
{
    public function __construct($http = null)
    {
        $modules = [
            'error-handler',
            'twig',
            'app',
        ];

        $dotenv = new Dotenv(__DIR__ . '/../../');
        $dotenv->load();

        parent::__construct($http, $modules);
    }

    protected function configureContainerBuilder(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->useAnnotations(true);
        return $containerBuilder;
    }
}
