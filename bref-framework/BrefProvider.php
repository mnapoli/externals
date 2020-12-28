<?php declare(strict_types=1);

namespace Bref\Framework;

use Bref\Framework\Http\MiddlewarePipe;
use Bref\Framework\Middleware\BodyParser;
use Bref\Framework\Middleware\NotFoundMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BrefProvider implements ContainerInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function get($id): object
    {
        $handler = $this->container->get($id);

        if ($handler instanceof RequestHandlerInterface) {
            $handler = new MiddlewarePipe($handler, $this->middlewares(), $this->container);
        }

        return $handler;
    }

    public function has($id): bool
    {
        return $this->container->has($id);
    }

    private function middlewares()
    {
        $middlewares = [
            NotFoundMiddleware::class,
            BodyParser::class,
        ];

        if ($this->container->has('http.middlewares')) {
            $middlewares = [
                ...$middlewares,
                ...$this->container->get('http.middlewares'),
            ];
        }

        return $middlewares;
    }
}
