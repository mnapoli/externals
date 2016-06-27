<?php
declare(strict_types = 1);

namespace Externals\Email;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ThreadItem
{
    /**
     * @var Email
     */
    private $email;

    /**
     * @var ThreadItem[]
     */
    private $replies = [];

    public function __construct(Email $email)
    {
        $this->email = $email;
    }

    public function getEmail() : Email
    {
        return $this->email;
    }

    /**
     * @return ThreadItem[]
     */
    public function getReplies() : array
    {
        return $this->replies;
    }

    public function addReply(ThreadItem $item)
    {
        $this->replies[] = $item;
    }
}
