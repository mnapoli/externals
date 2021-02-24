<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Bref\Micro\Controller;
use DI\Attribute\Inject;
use Externals\Email\EmailRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class HomeController extends Controller
{
    #[Inject('algolia.index_prefix')]
    private string $algoliaIndexPrefix;

    public function __construct(
        private Environment $twig,
        private EmailRepository $repository,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute('user');
        $page = (int) $this->queryParameter($request, 'page', 1);
        $perPage = 20;

        return $this->htmlResponse($this->twig->render('home.html.twig', [
            'threads' => $this->repository->findLatestThreads($page, $user),
            'page' => $page,
            'pageCount' => ceil($this->repository->getThreadCount() / $perPage),
            'user' => $user,
            'algoliaIndex' => $this->algoliaIndexPrefix . 'emails',
        ]));
    }
}
