<?php declare(strict_types=1);

namespace Externals\Email;

use DateTimeInterface;

/**
 * @psalm-immutable
 */
class Email
{
    public string $id;
    public int $number;
    public string $subject;
    public string $content;
    /** The raw source of the message */
    public string $source;
    public ?string $threadId;
    public DateTimeInterface $date;
    public EmailAddress $from;
    /** ID of the email it replies to */
    public ?string $inReplyTo;
    public bool $isRead = false;

    public function __construct(
        string $id,
        int $number,
        string $subject,
        string $content,
        string $source,
        ?string $threadId,
        DateTimeInterface $date,
        EmailAddress $from,
        ?string $inReplyTo = null
    ) {
        $this->id = $id;
        $this->number = $number;
        $this->subject = $subject;
        $this->content = $content;
        $this->source = $source;
        $this->threadId = $threadId;
        $this->date = $date;
        $this->from = $from;
        $this->inReplyTo = $inReplyTo;
    }

    /**
     * Unique NNTP message ID used in references.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * NNTP message number.
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * If null, then the message is the thread root.
     */
    public function getThreadId(): ?string
    {
        return $this->threadId;
    }

    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    public function getFrom(): EmailAddress
    {
        return $this->from;
    }

    public function getInReplyTo(): ?string
    {
        return $this->inReplyTo;
    }

    public function markAsRead(): void
    {
        $this->isRead = true;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function isThreadRoot(): bool
    {
        return $this->getThreadId() === $this->getId();
    }

    public function getUrl(): string
    {
        return '/' . $this->getId();
    }
}
