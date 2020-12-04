<?php
declare(strict_types=1);

namespace Bref\Framework\Http\Exception;

/**
 * HTTP exception: 400 invalid request.
 *
 * @psalm-immutable
 */
class HttpInvalidRequest extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct(400, $message);
    }
}
