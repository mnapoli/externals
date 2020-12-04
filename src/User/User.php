<?php declare(strict_types=1);

namespace Externals\User;

use JsonSerializable;

/**
 * @psalm-immutable
 */
class User implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $githubId,
        public string $name
    ) {
    }

    /**
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'githubId' => $this->githubId,
            'name' => $this->name,
        ];
    }

    public static function fromData(mixed $data): ?self
    {
        if (! is_array($data)) {
            return null;
        }

        return new self($data['id'], $data['githubId'], $data['name']);
    }
}
