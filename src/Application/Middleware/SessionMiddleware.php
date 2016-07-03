<?php
declare(strict_types = 1);

namespace Externals\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Stratify\Http\Middleware\Middleware;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Creates and set a session object in the request.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class SessionMiddleware implements Middleware
{
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ) : ResponseInterface
    {
        $session = new Session;

        $request = $request->withAttribute(SessionInterface::class, $session);

        return $next($request, $response);
    }
}
