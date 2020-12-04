<?php declare(strict_types=1);

namespace Bref\Framework;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Stringable;

class HttpResponse
{
    public static function html(string|Stringable $body, int $status = 200): ResponseInterface
    {
        return new Response($status, [
            'Content-Type' => 'text/html; charset=utf-8',
        ], (string) $body);
    }

    public static function json(mixed $data, int $status = 200): ResponseInterface
    {
        return new Response($status, [
            'Content-Type' => 'application/json',
        ], json_encode($data, JSON_THROW_ON_ERROR));
    }

    public static function redirect(string|Stringable|UriInterface $uri, int $status = 302): ResponseInterface
    {
        return new Response($status, [
            'Location' => (string) $uri,
        ]);
    }
}
