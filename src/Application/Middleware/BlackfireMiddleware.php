<?php
declare(strict_types=1);

namespace Externals\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Middleware to enable blackfire in a React application.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class BlackfireMiddleware
{
    public function __invoke(ServerRequestInterface $request, callable $next) : ResponseInterface
    {
        $query = $request->getHeaderLine('x-blackfire-query');

        // Only enable when the X-Blackfire-Query header is present
        if (! $query) {
            return $next($request);
        }

        $probe = new \BlackfireProbe($query);
        if (! $probe->enable()) {
            return $next($request);
        }

        /** @var ResponseInterface $response */
        $response = $next($request);

        // Stop profiling once the request ends
        $probe->close();

        // Return the header
        $header = explode(':', $probe->getResponseLine(), 2);

        return $response->withHeader('x-' . $header[0], trim($header[1]));
    }
}
