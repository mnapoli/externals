<?php

declare(strict_types=1);

namespace App\Support\Email;

/**
 * Email address that may be associated to a name.
 *
 * Example: "John Doe" <john@example.com>
 */
class EmailAddress
{
    public function __construct(
        public readonly ?string $email = null,
        public readonly ?string $name = null,
    ) {}

    public function getNameOrEmail(): string
    {
        return $this->name ?: ($this->email ?? '');
    }

    public function __toString(): string
    {
        if ($this->name === null) {
            return (string) $this->email;
        }

        return sprintf('"%s" <%s>', $this->name, $this->email);
    }
}
