<?php
declare(strict_types = 1);

use Externals\Application\Application;
use Externals\Application\Controller\NotFoundController;
use Externals\Email\EmailRepository;
use Externals\Thread\ThreadRepository;
use Stratify\ErrorHandlerModule\ErrorHandlerMiddleware;
use function Stratify\Framework\pipe;
use function Stratify\Framework\router;
use function Stratify\Router\route;

if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']))) {
    return false;
}

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../.puli/GeneratedPuliFactory.php';

$http = pipe([
    ErrorHandlerMiddleware::class,

    router([
        '/' => function (Twig_Environment $twig, ThreadRepository $threadRepository) {
            return $twig->render('/app/views/home.html.twig', [
                'threads' => $threadRepository->findLatest(),
            ]);
        },
        '/thread/{id}' => function (int $id, Twig_Environment $twig, ThreadRepository $threadRepository, EmailRepository $emailRepository) {
            return $twig->render('/app/views/thread.html.twig', [
                'subject' => $threadRepository->getSubject($id),
                'thread' => $emailRepository->getThreadView($id),
            ]);
        },
    ]),

    NotFoundController::class,
]);

$app = new Application();
$app->http($http)->run();
