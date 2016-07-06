<?php
declare(strict_types = 1);

namespace Externals\Application\Middleware;

use Externals\Application\Controller\NotFoundController;
use Externals\NotFound;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Stratify\Http\Middleware\Middleware;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class NotFoundMiddleware implements Middleware
{
    /**
     * @var NotFoundController
     */
    private $controller;

    public function __construct(NotFoundController $controller)
    {
        $this->controller = $controller;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ) : ResponseInterface
    {
        try {
            return $next($request, $response);
        } catch (NotFound $e) {
            return $this->controller->__invoke($request);
        }
    }
}
