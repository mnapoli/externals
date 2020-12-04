<?php declare(strict_types=1);

namespace Bref\Framework\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Class imported from the Slim Framework (https://slimframework.com), licensed under the MIT License.
 *
 * Copyright (c) 2011-2020 Josh Lockhart
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 * @see https://github.com/slimphp/Slim/blob/1b098e7d8dafb46713258e70f444d36f1133ab23/Slim/Middleware/BodyParsingMiddleware.php
 */
class BodyParser implements MiddlewareInterface
{
    /** @var callable[] */
    private array $bodyParsers;

    /**
     * @param callable[] $bodyParsers list of body parsers as an associative array of mediaType => callable
     */
    public function __construct(array $bodyParsers = [])
    {
        $this->registerDefaultBodyParsers();

        foreach ($bodyParsers as $mediaType => $parser) {
            $this->registerBodyParser($mediaType, $parser);
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        if ($parsedBody === null || empty($parsedBody)) {
            $parsedBody = $this->parseBody($request);
            $request = $request->withParsedBody($parsedBody);
        }

        return $handler->handle($request);
    }

    public function registerBodyParser(string $mediaType, callable $callable): self
    {
        $this->bodyParsers[$mediaType] = $callable;
        return $this;
    }

    public function hasBodyParser(string $mediaType): bool
    {
        return isset($this->bodyParsers[$mediaType]);
    }

    public function getBodyParser(string $mediaType): callable
    {
        if (!isset($this->bodyParsers[$mediaType])) {
            throw new RuntimeException('No parser for type ' . $mediaType);
        }
        return $this->bodyParsers[$mediaType];
    }

    private function registerDefaultBodyParsers(): void
    {
        $this->registerBodyParser('application/json', static function ($input) {
            $result = json_decode($input, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($result)) {
                return null;
            }

            return $result;
        });

        $this->registerBodyParser('application/x-www-form-urlencoded', static function ($input) {
            parse_str($input, $data);
            return $data;
        });
    }

    private function parseBody(ServerRequestInterface $request): array|object|null
    {
        $mediaType = $this->getMediaType($request);
        if ($mediaType === null) {
            return null;
        }

        // Check if this specific media type has a parser registered first
        if (!isset($this->bodyParsers[$mediaType])) {
            // If not, look for a media type with a structured syntax suffix (RFC 6839)
            $parts = explode('+', $mediaType);
            if (count($parts) >= 2) {
                $mediaType = 'application/' . $parts[count($parts) - 1];
            }
        }

        if (isset($this->bodyParsers[$mediaType])) {
            $body = (string)$request->getBody();
            $parsed = $this->bodyParsers[$mediaType]($body);

            if (!is_null($parsed) && !is_object($parsed) && !is_array($parsed)) {
                throw new RuntimeException(
                    'Request body media type parser return value must be an array, an object, or null'
                );
            }

            return $parsed;
        }

        return null;
    }

    private function getMediaType(ServerRequestInterface $request): ?string
    {
        $contentType = $request->getHeader('Content-Type')[0] ?? null;

        if (is_string($contentType) && trim($contentType) !== '') {
            $contentTypeParts = explode(';', $contentType);
            return strtolower(trim($contentTypeParts[0]));
        }

        return null;
    }
}
