<?php
declare(strict_types = 1);

namespace Externals\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Stratify\Http\Middleware\Middleware;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Set the user in the request.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class AuthMiddleware implements Middleware
{
    public function __invoke(ServerRequestInterface $request, callable $next) : ResponseInterface
    {
        $session = $request->getAttribute(SessionInterface::class);

        $request = $request->withAttribute('user', $session->get('user'));

        return $next($request);
    }
}
