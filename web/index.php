<?php
declare(strict_types = 1);

use Externals\Application\Application;
use Externals\Application\Controller\NotFoundController;
use Externals\Email\Email;
use Externals\Email\EmailRepository;
use Externals\Thread\ThreadRepository;
use Stratify\ErrorHandlerModule\ErrorHandlerMiddleware;
use Zend\Diactoros\Response\JsonResponse;
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
                'emails' => $emailRepository->findByThread($id),
            ]);
        },
        '/api/threads/{id}/messages' => function (int $id, EmailRepository $emailRepository) {
            $emails = $emailRepository->findByThread($id);
            $data = array_map(function (Email $email) {
                return [
                    'id' => $email->getId(),
                    'subject' => $email->getSubject(),
                    'content' => $email->getContent(),
                    'date' => $email->getDate()->format('U'),
                ];
            }, $emails);
            return new JsonResponse($data);
        },
    ]),

    NotFoundController::class,
]);

$app = new Application();
$app->http($http)->run();
