<?php
declare(strict_types = 1);

use Doctrine\DBAL\Connection;
use Externals\Application\Controller\AuthController;
use Externals\Application\Controller\NotFoundController;
use Externals\Application\Middleware\AuthMiddleware;
use Externals\Application\Middleware\NotFoundMiddleware;
use Externals\Application\Middleware\SessionMiddleware;
use Externals\Email\EmailRepository;
use Externals\NotFound;
use Externals\User\User;
use Externals\User\UserRepository;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Stratify\ErrorHandlerModule\ErrorHandlerMiddleware;
use function Stratify\Framework\pipe;
use function Stratify\Framework\router;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Response\TextResponse;

/**
 * HTTP stack.
 */
return pipe([
    ErrorHandlerMiddleware::class,
    NotFoundMiddleware::class,
    SessionMiddleware::class,
    AuthMiddleware::class,

    router([

        '/' => function (Twig_Environment $twig, EmailRepository $repository, ServerRequestInterface $request, ContainerInterface $container) {
            newrelic_name_transaction('home');
            $user = $request->getAttribute('user');
            return $twig->render('@app/home.html.twig', [
                'threads' => $repository->findLatestThreads(1, $user),
                'user' => $user,
                'algoliaIndex' => $container->get('algolia.index_prefix') . 'emails',
            ]);
        },

        '/message/{number}' => function (int $number, Twig_Environment $twig, EmailRepository $repository, ServerRequestInterface $request) {
            newrelic_name_transaction('message');
            newrelic_add_custom_parameter('message', $number);

            $email = $repository->getByNumber($number);
            if (!$email->isThreadRoot()) {
                // The email is in a thread => redirect to the thread root
                try {
                    $thread = $repository->getById($email->getThreadId());
                    return new RedirectResponse("/message/{$thread->getNumber()}#$number");
                } catch (NotFound $e) {
                    // We cannot find the root message
                }
            }

            $user = $request->getAttribute('user');
            $emailCount = $repository->getThreadSize($email);
            // Get thread view **before** marking the thread as read
            $threadView = $repository->getThreadView($email, $user);
            if ($user instanceof User) {
                $repository->markAsRead($email, $user);
            }
            return $twig->render('@app/thread.html.twig', [
                'subject' => $email->getSubject(),
                'thread' => $threadView,
                'threadId' => $number,
                'emailCount' => $emailCount,
                'user' => $user,
            ]);
        },

        '/threads/list' => function (Twig_Environment $twig, EmailRepository $repository, ServerRequestInterface $request) {
            newrelic_name_transaction('thread-list');
            $user = $request->getAttribute('user');
            $query = $request->getQueryParams();
            $page = (int) max(1, $query['page'] ?? 1);
            return $twig->render('@app/threads/thread-list.html.twig', [
                'threads' => $repository->findLatestThreads($page, $user),
                'user' => $user,
            ]);
        },

        '/login' => [AuthController::class, 'login'],
        '/logout' => [AuthController::class, 'logout'],

        '/stats' => function (Twig_Environment $twig, EmailRepository $emailRepository, UserRepository $userRepository, ServerRequestInterface $request) {
            newrelic_name_transaction('stats');
            $user = $request->getAttribute('user');
            return $twig->render('@app/stats.html.twig', [
                'userCount' => $userRepository->getUserCount(),
                'threadCount' => $emailRepository->getThreadCount(),
                'emailCount' => $emailRepository->getEmailCount(),
                'user' => $user,
            ]);
        },

        '/email/{number}/source' => function (int $number, EmailRepository $emailRepository) {
            newrelic_name_transaction('email_source');
            return new TextResponse($emailRepository->getEmailSource($number));
        },

        // Keep backward compatibility with old URLs (old threads)
        '/thread/{id}' => function (int $id, EmailRepository $emailRepository, Connection $db) {
            $threadSubject = $db->fetchColumn('SELECT `subject` FROM threads_old WHERE id = ?', [$id]);
            $email = $emailRepository->findBySubject($threadSubject);
            // Permanent redirection
            return new RedirectResponse("/message/{$email->getNumber()}", 301);
        },
    ]),

    NotFoundController::class,
]);
