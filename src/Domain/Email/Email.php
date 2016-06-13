<?php
declare(strict_types = 1);

namespace Externals\Domain\Email;

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

    public function __construct(
        string $id,
        string $subject,
        string $content,
        string $originalContent
    ) {
        $this->id = $id;
        $this->subject = $subject;
        $this->content = $content;
        $this->originalContent = $originalContent;
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

    public function getOriginalContent() : string
    {
        return $this->originalContent;
    }
}
