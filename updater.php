<?php declare(strict_types = 1);

use Externals\EmailSynchronizer;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = require __DIR__ . '/config/bootstrap.php';

$synchronizer = $container->get(EmailSynchronizer::class);

return fn() => $synchronizer->synchronize();
