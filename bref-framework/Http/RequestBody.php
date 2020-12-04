<?php declare(strict_types=1);

namespace Bref\Framework\Http;

use App\Framework\Http\Exception\HttpInvalidRequest;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionProperty;

class RequestBody
{
    public function __construct(ServerRequestInterface $request)
    {
        $class = new ReflectionClass($this);
        $properties = $class->getProperties();

        $body = $request->getParsedBody();

        if (! is_array($body)) {
            throw new HttpInvalidRequest(sprintf(
                'Invalid body in HTTP request: missing fields %s',
                implode(', ', array_map(fn($property) => $property->getName(), $properties))
            ));
        }

        foreach ($properties as $property) {
            if ($property->isStatic()) continue;

            $field = $property->getName();
            $this->{$field} = $body[$field];
        }
    }
}
