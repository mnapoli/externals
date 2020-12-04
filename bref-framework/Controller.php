<?php declare(strict_types=1);

namespace Bref\Framework;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Stringable;

abstract class Controller implements RequestHandlerInterface
{
    public function htmlResponse(string|Stringable $body, int $status = 200): ResponseInterface
    {
        return new Response($status, [
            'Content-Type' => 'text/html; charset=utf-8',
        ], (string) $body);
    }

    public function jsonResponse(mixed $data, int $status = 200): ResponseInterface
    {
        return new Response($status, [
            'Content-Type' => 'application/json',
        ], json_encode($data, JSON_THROW_ON_ERROR));
    }

    public function redirectResponse(string|Stringable|UriInterface $uri, int $status = 302): ResponseInterface
    {
        return new Response($status, [
            'Location' => (string) $uri,
        ]);
    }

    public function textResponse(string|Stringable $body, int $status = 200): ResponseInterface
    {
        return new Response($status, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ], (string) $body);
    }

    public function queryParameter(ServerRequestInterface $request, string $parameter, $default = null): mixed
    {
        return $request->getQueryParams()[$parameter] ?? $default;
    }

    public function bodyField(ServerRequestInterface $request, string $field, $default = null): mixed
    {
        return $request->getParsedBody()[$field] ?? $default;
    }
}
