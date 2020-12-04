<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Bref\Framework\Controller;
use Externals\Email\EmailRepository;
use Externals\User\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class StatsController extends Controller
{
    public function __construct(
        private Environment $twig,
        private EmailRepository $emailRepository,
        private UserRepository $userRepository,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->htmlResponse($this->twig->render('stats.html.twig', [
            'userCount' => $this->userRepository->getUserCount(),
            'threadCount' => $this->emailRepository->getThreadCount(),
            'emailCount' => $this->emailRepository->getEmailCount(),
            'user' => $request->getAttribute('user'),
        ]));
    }
}
