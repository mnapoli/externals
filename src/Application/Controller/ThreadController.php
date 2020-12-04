<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Bref\Framework\Controller;
use Externals\Email\EmailRepository;
use Externals\NotFound;
use Externals\User\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class ThreadController extends Controller
{
    public function __construct(
        private Environment $twig,
        private EmailRepository $repository
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $number = (int) $request->getAttribute('number');

        $email = $this->repository->getByNumber($number);
        if (!$email->isThreadRoot()) {
            // The email is in a thread => redirect to the thread root
            try {
                $thread = $this->repository->getById($email->getThreadId());
                return $this->redirectResponse("/message/{$thread->getNumber()}#$number");
            } catch (NotFound) {
                // We cannot find the root message
                // We will display the thread from this URL
            }
        }

        $user = $request->getAttribute('user');
        $emailCount = $this->repository->getThreadSize($email);
        // Get thread view **before** marking the thread as read
        $threadView = $this->repository->getThreadView($email, $user);
        if ($user instanceof User) {
            $this->repository->markAsRead($email, $user);
        }

        return $this->htmlResponse($this->twig->render('thread.html.twig', [
            'subject' => $email->getSubject(),
            'thread' => $threadView,
            'threadId' => $number,
            'emailCount' => $emailCount,
            'user' => $user,
        ]));
    }
}
