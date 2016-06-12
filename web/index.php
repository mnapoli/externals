<?php

use Externals\Domain\Thread\ThreadRepository;
use Stratify\ErrorHandlerModule\ErrorHandlerMiddleware;
use function Stratify\Framework\pipe;
use function Stratify\Framework\router;
use function Stratify\Router\route;

if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']))) {
    return false;
}

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../.puli/GeneratedPuliFactory.php';

$modules = [
    'error-handler',
    'twig',
    'app',
];

$http = pipe([
    ErrorHandlerMiddleware::class,

    router([
        '/' => function (Twig_Environment $twig, ThreadRepository $threadRepository) {
            return $twig->render('/app/views/home.html.twig', [
                'threads' => $threadRepository->findLatest(),
            ]);
        },
    ]),
]);

$app = new \Externals\Application\Application($http, $modules);
$app->runHttp();
