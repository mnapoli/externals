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
     * @var string
     */
    private $subject;

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
     * @var string|null
     */
    private $imapId;

    /**
     * ID ($imapId) of the message it replies to.
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
        string $subject,
        string $content,
        string $originalContent,
        int $threadId,
        DateTimeInterface $date,
        EmailAddress $from,
        $imapId,
        $inReplyTo
    ) {
        $this->id = $id;
        $this->subject = $subject;
        $this->content = $content;
        $this->originalContent = $originalContent;
        $this->threadId = $threadId;
        $this->date = $date;
        $this->from = $from;
        $this->imapId = $imapId;
        $this->inReplyTo = $inReplyTo;
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function getSubject() : string
    {
        return $this->subject;
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
    public function getImapId()
    {
        return $this->imapId;
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
