<?php
declare(strict_types = 1);

use Externals\Application\Application;
use Externals\Application\Controller\AuthController;
use Externals\Application\Controller\NotFoundController;
use Externals\Application\Middleware\SessionMiddleware;
use Externals\Email\EmailRepository;
use Externals\Thread\ThreadRepository;
use Psr\Http\Message\ServerRequestInterface;
use Stratify\ErrorHandlerModule\ErrorHandlerMiddleware;
use function Stratify\Framework\pipe;
use function Stratify\Framework\router;
use Zend\Diactoros\Response\JsonResponse;

if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']))) {
    return false;
}

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../.puli/GeneratedPuliFactory.php';

$http = pipe([
    ErrorHandlerMiddleware::class,
    SessionMiddleware::class,

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
                'threadId' => $id,
                'emailCount' => $emailRepository->getThreadCount($id),
            ]);
        },
        '/api/threads' => function (ThreadRepository $threadRepository, ServerRequestInterface $request) {
            $query = $request->getQueryParams();
            $page = (int) max(1, $query['page'] ?? 1);
            return new JsonResponse($threadRepository->findLatest($page));
        },
        '/login' => [AuthController::class, 'login'],
    ]),

    NotFoundController::class,
]);

$app = new Application();
$app->http($http)->run();
