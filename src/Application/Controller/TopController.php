<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Bref\Framework\Controller;
use Externals\Email\EmailRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class TopController extends Controller
{
    public function __construct(
        private Environment $twig,
        private EmailRepository $repository
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute('user');

        return $this->htmlResponse($this->twig->render('top.html.twig', [
            'threads' => $this->repository->findTopThreads(1, $user),
            'user' => $user,
        ]));
    }
}
