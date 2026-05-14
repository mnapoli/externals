<?php

declare(strict_types=1);

namespace App\Email;

class ThreadItem
{
    /** @var ThreadItem[] */
    public array $replies = [];

    public function __construct(public readonly Email $email) {}

    public function addReply(self $item): void
    {
        $this->replies[] = $item;
    }
}
