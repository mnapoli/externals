<?php
declare(strict_types = 1);

namespace Externals\Application\Middleware;

use Externals\User\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PSR7Session\Http\SessionMiddleware;
use Stratify\Http\Middleware\Middleware;

/**
 * Set the user in the request.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class AuthMiddleware implements Middleware
{
    public function __invoke(ServerRequestInterface $request, callable $next) : ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        $request = $request->withAttribute('user', User::fromData($session->get('user')));

        return $next($request);
    }
}
