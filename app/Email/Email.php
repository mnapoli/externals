<?php

declare(strict_types=1);

namespace App\Email;

use DateTimeInterface;

class Email
{
    public bool $isRead = false;

    public function __construct(
        public readonly string $id,
        public readonly int $number,
        public readonly string $subject,
        public string $content,
        public readonly string $source,
        public readonly ?string $threadId,
        public readonly DateTimeInterface $date,
        public readonly EmailAddress $from,
        public readonly ?string $inReplyTo = null,
    ) {}

    public function isThreadRoot(): bool
    {
        return $this->threadId === $this->id;
    }

    public function getUrl(): string
    {
        return '/'.$this->id;
    }
}
