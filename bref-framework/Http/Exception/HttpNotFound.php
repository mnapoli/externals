<?php declare(strict_types = 1);

namespace Bref\Framework\Http\Exception;

/**
 * HTTP exception: 404 not found.
 *
 * @psalm-immutable
 */
class HttpNotFound extends HttpException
{
    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct(404, $message);
    }
}
