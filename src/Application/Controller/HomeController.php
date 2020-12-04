<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Bref\Framework\Controller;
use DI\Attribute\Inject;
use Externals\Email\EmailRepository;
use Psr\Container\ContainerInterface;
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

        return $this->htmlResponse($this->twig->render('home.html.twig', [
            'threads' => $this->repository->findLatestThreads(1, $user),
            'user' => $user,
            'algoliaIndex' => $this->algoliaIndexPrefix . 'emails',
        ]));
    }
}
