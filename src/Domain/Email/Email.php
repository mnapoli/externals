<?php
declare(strict_types = 1);

namespace Externals\Domain\Email;

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

    public function __construct(
        string $id,
        string $subject,
        string $content,
        string $originalContent,
        int $threadId,
        DateTimeInterface $date,
        EmailAddress $from
    ) {
        $this->id = $id;
        $this->subject = $subject;
        $this->content = $content;
        $this->originalContent = $originalContent;
        $this->threadId = $threadId;
        $this->date = $date;
        $this->from = $from;
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
}
