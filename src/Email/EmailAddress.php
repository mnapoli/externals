<?php declare(strict_types=1);

namespace Externals\Email;

/**
 * Email address that may be associated to a name.
 *
 * Example: "John Doe" <john@example.com>
 */
class EmailAddress
{
    public function __construct(
        private ?string $email = null,
        private ?string $name = null,
    ) {
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getNameOrEmail(): string
    {
        return $this->name ?: $this->email;
    }

    public function __toString(): string
    {
        if ($this->name === null) {
            return $this->email;
        }

        return sprintf('"%s" <%s>', $this->name, $this->email);
    }
}
