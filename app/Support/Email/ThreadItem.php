<?php

declare(strict_types=1);

namespace App\Support\Email;

use App\Models\Email;

class ThreadItem
{
    /** @var ThreadItem[] */
    public array $replies = [];

    public ?ThreadItem $parent = null;

    public function __construct(
        public readonly Email $email,
    ) {}

    public function addReply(self $item): void
    {
        $item->parent = $this;
        $this->replies[] = $item;
    }

    /**
     * 0 for a message at the root of the thread.
     */
    public function depth(): int
    {
        return $this->parent === null ? 0 : $this->parent->depth() + 1;
    }

    public function descendantCount(): int
    {
        return array_sum(array_map(fn(self $reply) => 1 + $reply->descendantCount(), $this->replies));
    }

    public function unreadDescendantCount(): int
    {
        return array_sum(array_map(fn(self $reply) => ($reply->email->isRead ? 0 : 1) + $reply->unreadDescendantCount(), $this->replies));
    }

    /**
     * This message and all its replies, depth first, in reading order.
     *
     * @return list<self>
     */
    public function flatten(): array
    {
        $items = [$this];
        foreach ($this->replies as $reply) {
            array_push($items, ...$reply->flatten());
        }

        return $items;
    }
}
