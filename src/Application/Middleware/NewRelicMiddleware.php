<?php
declare(strict_types=1);

namespace Externals\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Middleware to enable New Relic in a React application.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class NewRelicMiddleware
{
    public function __invoke(ServerRequestInterface $request, callable $next) : ResponseInterface
    {
        newrelic_end_transaction();
        newrelic_start_transaction(ini_get('newrelic.appname'));

        /** @var ResponseInterface $response */
        $response = $next($request);

        newrelic_end_transaction(false);

        return $response;
    }
}
