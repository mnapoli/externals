<?php declare(strict_types=1);

namespace Bref\Framework\Http;

use Psr\Http\Message\ResponseInterface;

class ResponseEmitter
{
    public function emit(ResponseInterface $response): void
    {
        $this->emitHeaders($response);
        $this->emitStatusLine($response);
        echo $response->getBody();
    }

    private function emitStatusLine(ResponseInterface $response): void
    {
        $reasonPhrase = $response->getReasonPhrase();
        $statusCode = $response->getStatusCode();

        header(sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $statusCode,
            ($reasonPhrase ? ' ' . $reasonPhrase : '')
        ), true, $statusCode);
    }

    private function emitHeaders(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        foreach ($response->getHeaders() as $header => $values) {
            $name = $this->filterHeader($header);
            $first = $name === 'Set-Cookie' ? false : true;
            foreach ($values as $value) {
                header(sprintf(
                    '%s: %s',
                    $name,
                    $value
                ), $first, $statusCode);
                $first = false;
            }
        }
    }

    private function filterHeader(string $header): string
    {
        return ucwords($header, '-');
    }
}
