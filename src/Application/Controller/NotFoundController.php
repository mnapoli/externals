<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Zend\Diactoros\Response\HtmlResponse;

class NotFoundController
{
    /** @var Environment */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        newrelic_name_transaction('404');
        $response = new HtmlResponse($this->twig->render('404.html.twig', [
            'user' => $request->getAttribute('user'),
        ]));
        return $response->withStatus(404);
    }
}
