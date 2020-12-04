<?php declare(strict_types=1);

namespace Bref\Framework\Http;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Pipes middlewares to call them in order.
 *
 * This is also a middleware so that it can be used like any other middleware.
 */
class MiddlewarePipe implements MiddlewareInterface, RequestHandlerInterface
{
    /** @var MiddlewareInterface[] */
    private array $middlewares;

    public function __construct(
        private RequestHandlerInterface $handler,
        array $middlewares,
        private ContainerInterface $container
    ) {
        $this->middlewares = $middlewares;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->process($request, $this->handler);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Resolve middleware classes into instances using the container
        $middlewares = array_map(function (string|MiddlewareInterface $middleware): MiddlewareInterface {
            if ($middleware instanceof MiddlewareInterface) {
                return $middleware;
            }
            return $this->container->get($middleware);
        }, $this->middlewares);

        foreach (array_reverse($middlewares) as $middleware) {
            $handler = new MiddlewareInvokerDelegate($middleware, $handler);
        }

        // Invoke the root middleware
        return $handler->handle($request);
    }
}
