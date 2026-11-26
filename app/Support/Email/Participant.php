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
        public ?string $avatarUrl = null,
    ) {}
}
