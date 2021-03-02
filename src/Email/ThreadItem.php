<?php declare(strict_types=1);

namespace Externals\Email;

class ThreadItem
{
    private Email $email;

    /** @var ThreadItem[] */
    private array $replies = [];

    public function __construct(Email $email)
    {
        $this->email = $email;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    /**
     * @return ThreadItem[]
     */
    public function getReplies(): array
    {
        return $this->replies;
    }

    public function addReply(ThreadItem $item): void
    {
        $this->replies[] = $item;
    }
}
