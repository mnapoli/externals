<?php declare(strict_types=1);

namespace Externals\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PSR7Session\Http\SessionMiddleware as Psr7Middleware;
use Stratify\Http\Middleware\Middleware;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * Creates and set a session object in the request.
 */
class SessionMiddleware implements Middleware
{
    /** @var Psr7Middleware */
    private $middleware;

    public function __construct(Psr7Middleware $middleware)
    {
        $this->middleware = $middleware;
    }

    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        return ($this->middleware)($request, new EmptyResponse, $next);
    }
}
