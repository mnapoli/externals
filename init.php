<?php declare(strict_types=1);

use Bref\Bref;
use Bref\Micro\BrefProvider;

require_once __DIR__ . '/vendor/autoload.php';

Bref::setContainer(function () {
    $container = require __DIR__ . '/res/bootstrap.php';
    return new BrefProvider($container);
});
