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

    public function __toString(): string
    {
        if ($this->name === null) {
            return (string) $this->email;
        }

        return sprintf('"%s" <%s>', $this->name, $this->email);
    }

    /**
     * Gravatar rather than unavatar.io, which allows 25 avatars per day and per visitor: lists show dozens.
     * Without a photo, Gravatar serves a transparent image so that the initials underneath show through.
     */
    public static function avatarUrlOf(?string $email, int $size = 64): ?string
    {
        if ($email === null || $email === '') {
            return null;
        }

        return 'https://www.gravatar.com/avatar/' . hash('sha256', mb_strtolower(mb_trim($email))) . "?s={$size}&d=blank";
    }

    public function getNameOrEmail(): string
    {
        return $this->name ?: ($this->email ?? '');
    }
}
