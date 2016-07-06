<?php
declare(strict_types = 1);

use Externals\Application\Application;
use Externals\Application\Controller\AuthController;
use Externals\Application\Controller\NotFoundController;
use Externals\Application\Middleware\AuthMiddleware;
use Externals\Application\Middleware\SessionMiddleware;
use Externals\Email\EmailRepository;
use Externals\Thread\ThreadRepository;
use Externals\User\User;
use Externals\User\UserRepository;
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
    AuthMiddleware::class,

    router([
        '/' => function (Twig_Environment $twig, ThreadRepository $threadRepository, ServerRequestInterface $request) {
            newrelic_name_transaction('home');
            $user = $request->getAttribute('user');
            return $twig->render('/app/views/home.html.twig', [
                'threads' => $threadRepository->findLatest(1, $user),
                'user' => $user,
            ]);
        },
        '/thread/{id}' => function (int $id, Twig_Environment $twig, ThreadRepository $threadRepository, EmailRepository $emailRepository, ServerRequestInterface $request) {
            newrelic_name_transaction('thread');
            newrelic_add_custom_parameter('thread', $id);
            $user = $request->getAttribute('user');
            $emailCount = $emailRepository->getThreadCount($id);
            // Get thread view **before** marking the thread as read
            $threadView = $emailRepository->getThreadView($id);
            if ($user instanceof User) {
                $threadRepository->markThreadRead($id, $user, $emailCount);
            }
            return $twig->render('/app/views/thread.html.twig', [
                'subject' => $threadRepository->getSubject($id),
                'thread' => $threadView,
                'threadId' => $id,
                'emailCount' => $emailCount,
                'user' => $user,
            ]);
        },
        '/threads/list' => function (Twig_Environment $twig, ThreadRepository $threadRepository, ServerRequestInterface $request) {
            newrelic_name_transaction('thread-list');
            $user = $request->getAttribute('user');
            $query = $request->getQueryParams();
            $page = (int) max(1, $query['page'] ?? 1);
            return $twig->render('/app/views/threads/thread-list.html.twig', [
                'threads' => $threadRepository->findLatest($page, $user),
                'user' => $user,
            ]);
        },
        '/login' => [AuthController::class, 'login'],
        '/logout' => [AuthController::class, 'logout'],
        '/stats' => function (Twig_Environment $twig, ThreadRepository $threadRepository, EmailRepository $emailRepository, UserRepository $userRepository, ServerRequestInterface $request) {
            newrelic_name_transaction('stats');
            $user = $request->getAttribute('user');
            return $twig->render('/app/views/stats.html.twig', [
                'userCount' => $userRepository->getUserCount(),
                'threadCount' => $threadRepository->getThreadCount(),
                'emailCount' => $emailRepository->getEmailCount(),
                'user' => $user,
            ]);
        },
    ]),

    NotFoundController::class,
]);

$app = new Application();
$app->http($http)->run();
