<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Bref\Framework\Controller;
use Externals\Email\EmailRepository;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class MoreThreadsController extends Controller
{
    public function __construct(
        private Environment $twig,
        private EmailRepository $repository,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute('user');
        $page = (int) max(1, $this->queryParameter($request, 'page', 1));

        return $this->htmlResponse($this->twig->render('threads/thread-list.html.twig', [
            'threads' => $this->repository->findLatestThreads($page, $user),
            'user' => $user,
        ]));
    }
}
