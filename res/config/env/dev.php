<?php
declare(strict_types = 1);

use function DI\get;

return [

    'debug' => true,

    'twig.options' => [
        'debug' => get('debug'),
        'cache' => false,
        'strict_variables' => true,
    ],

    'algolia.index_prefix' => 'dev_',

];
