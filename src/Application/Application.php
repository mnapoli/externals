<?php

namespace Externals\Application;

use DI\ContainerBuilder;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class Application extends \Stratify\Framework\Application
{
    protected function createContainerBuilder(array $modules) : ContainerBuilder
    {
        $builder = parent::createContainerBuilder($modules);
        $builder->useAnnotations(true);
        return $builder;
    }
}
