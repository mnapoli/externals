<?php declare(strict_types=1);

namespace Bref\Framework\Http\Exception;

/**
 * An exception that represents a HTTP error response.
 *
 * @psalm-immutable
 */
class HttpException extends \Exception
{
    public function __construct(
        public int $statusCode,
        string $message = null,
        \Exception $previous = null,
        public array $headers = [],
    ) {
        parent::__construct($message ?? '', 0, $previous);
    }
}
