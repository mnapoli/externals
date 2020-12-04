<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Bref\Framework\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class NewsController extends Controller
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->htmlResponse($this->twig->render('news.html.twig', [
            'user' => $request->getAttribute('user'),
        ]));
    }
}
