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
            'error-handler',
            'twig',
            'app',
        ];

        $environment = getenv('ENV') ?? 'prod';

        parent::__construct($modules, $environment);
    }
}
