<?php
declare(strict_types = 1);

use Doctrine\DBAL\Connection;
use Externals\Application\Controller\NotFoundController;
use Externals\Application\Middleware\AuthMiddleware;
use Externals\Application\Middleware\NotFoundMiddleware;
use Externals\Email\EmailRepository;
use Externals\RssBuilder;
use Externals\RssRfcBuilder;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * HTTP stack.
 */
return pipe([
    NotFoundMiddleware::class,
    AuthMiddleware::class,

    NotFoundController::class,
]);
