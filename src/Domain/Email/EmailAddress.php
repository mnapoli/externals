<?php

namespace Externals\Domain\Email;

/**
 * Email address that may be associated to a name.
 *
 * Example: "John Doe" <john@example.com>
 */
class EmailAddress
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $name;

    public function __construct(string $email, string $name = null)
    {
        $this->email = $email;
        $this->name = $name;
    }

    public function getEmail() : string
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    public function getNameOrEmail() : string
    {
        return $this->name ?: $this->email;
    }

    public function __toString() : string
    {
        if ($this->name === null) {
            return $this->email;
        }

        return sprintf('"%s" <%s>', $this->name, $this->email);
    }
}
