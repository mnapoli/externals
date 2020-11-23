<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Zend\Diactoros\Response\HtmlResponse;

class NotFoundController
{
    /** @var Environment */
    private $twig;
    /** @var LoggerInterface */
    private LoggerInterface $logger;

    public function __construct(Environment $twig, LoggerInterface $logger)
    {
        $this->twig = $twig;
        $this->logger = $logger;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->error('Not found: ' . $request->getUri() . PHP_EOL . json_encode([
            '$request->getServerParams()' => $request->getServerParams(),
            '$request->getUri()->getPath()' => $request->getUri()->getPath(),
            '$request->getUri()->getHost()' => $request->getUri()->getHost(),
            '$_SERVER' => $_SERVER,
        ]));

        newrelic_name_transaction('404');
        $response = new HtmlResponse($this->twig->render('404.html.twig', [
            'user' => $request->getAttribute('user'),
        ]));
        return $response->withStatus(404);
    }
}
