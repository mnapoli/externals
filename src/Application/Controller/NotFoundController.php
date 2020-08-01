<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

class NotFoundController
{
    /** @var \Twig_Environment */
    private $twig;

    public function __construct(\Twig_Environment $twig)
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
