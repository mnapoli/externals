<?php declare(strict_types=1);

namespace Externals\Application\Middleware;

use DI\Attribute\Inject;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

/**
 * Displays a "Maintenance" page.
 */
class MaintenanceMiddleware implements MiddlewareInterface
{
    public function __construct(
        private bool $maintenance,
        private Environment $twig,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->maintenance) {
            return new Response(200, [
                'Content-Type' => 'text/html',
            ], $this->twig->render('maintenance.html.twig'));
        }

        return $handler->handle($request);
    }
}
