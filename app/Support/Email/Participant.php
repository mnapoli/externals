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

    /**
     * Background of the initials: the same color for the same person on every page.
     */
    public function color(): string
    {
        $colors = ['#5b6bb5', '#8a5a2b', '#2f7d6b', '#6b4fa3', '#3a7a3a', '#b5533c', '#9b4d8a', '#2f6f8f', '#7a5c2e', '#4b5064'];

        return $colors[crc32(mb_strtolower($this->email ?? $this->name)) % count($colors)];
    }
}
