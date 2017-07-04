<?php

namespace Externals\Application;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class Application extends \Stratify\Framework\Application
{
    public function __construct()
    {
        $modules = [
            'stratify/error-handler-module',
            'stratify/twig-module',
            'mnapoli/externals',
        ];

        $environment = getenv('ENV') ?? 'prod';

        $httpStack = require(__DIR__ . '/../../res/http.php');

        parent::__construct($modules, $environment, $httpStack);
    }
}
