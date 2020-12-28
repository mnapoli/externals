<?php declare(strict_types=1);

namespace Externals\Application\Middleware;

use Bref\Framework\Http\Exception\HttpNotFound;
use Externals\NotFound;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class NotFoundMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (NotFound $e) {
            throw new HttpNotFound($e->getMessage());
        }
    }
}
