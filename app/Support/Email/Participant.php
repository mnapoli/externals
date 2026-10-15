<?php

declare(strict_types=1);

namespace App\Support\Email;

/**
 * Someone who wrote in a thread.
 */
final readonly class Participant
{
    public function __construct(
        public string $name,
        public ?string $email,
        public int $messageCount,
    ) {}

    /**
     * Shown under the photo, or instead of it when the person has none.
     */
    public function initials(): string
    {
        $words = array_values(array_filter(preg_split('/[\s.@_-]+/u', $this->name) ?: []));

        return implode('', array_map(fn(string $word) => mb_strtoupper(mb_substr($word, 0, 1)), array_slice($words, 0, 2)));
    }

    public function avatarUrl(): ?string
    {
        return EmailAddress::avatarUrlOf($this->email);
    }
}
