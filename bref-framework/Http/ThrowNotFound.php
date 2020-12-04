<?php declare(strict_types=1);

namespace Bref\Framework\Http;

use Bref\Framework\Http\Exception\HttpNotFound;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ThrowNotFound implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw new HttpNotFound('Page not found');
    }
}
