<?php
declare(strict_types = 1);

namespace Externals\Email;

use DateTimeInterface;

class Email
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var int
     */
    private $number;

    /**
     * @var string
     */
    private $content;

    /**
     * @var string
     */
    private $originalContent;

    /**
     * @var int
     */
    private $threadId;

    /**
     * @var DateTimeInterface
     */
    private $date;

    /**
     * @var EmailAddress
     */
    private $from;

    /**
     * ID of the email it replies to.
     *
     * @var string|null
     */
    private $inReplyTo;

    /**
     * @var bool
     */
    private $isRead = false;

    public function __construct(
        string $id,
        int $number,
        string $content,
        string $originalContent,
        int $threadId,
        DateTimeInterface $date,
        EmailAddress $from,
        string $inReplyTo = null
    ) {
        $this->id = $id;
        $this->number = $number;
        $this->content = $content;
        $this->originalContent = $originalContent;
        $this->threadId = $threadId;
        $this->date = $date;
        $this->from = $from;
        $this->inReplyTo = $inReplyTo;
    }

    /**
     * Unique NNTP message ID used in references.
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * NNTP message number.
     */
    public function getNumber() : int
    {
        return $this->number;
    }

    public function getContent() : string
    {
        return $this->content;
    }

    public function setContent(string $content)
    {
        $this->content = $content;
    }

    public function getOriginalContent() : string
    {
        return $this->originalContent;
    }

    public function getThreadId() : int
    {
        return $this->threadId;
    }

    public function getDate() : DateTimeInterface
    {
        return $this->date;
    }

    public function getFrom() : EmailAddress
    {
        return $this->from;
    }

    /**
     * @return null|string
     */
    public function getInReplyTo()
    {
        return $this->inReplyTo;
    }

    public function markAsRead()
    {
        $this->isRead = true;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }
}
