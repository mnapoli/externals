<?php declare(strict_types=1);

namespace Bref\Framework\Middleware;

use Bref\Framework\Http\Exception\HttpNotFound;
use Nyholm\Psr7\Response;
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
        } catch (HttpNotFound $e) {
            return new Response($e->statusCode, $e->headers, $e->getMessage());
        }
    }
}
