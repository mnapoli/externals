<?php
declare(strict_types = 1);

namespace Externals\Domain\Command\Handler;

use Doctrine\DBAL\Connection;
use Externals\Domain\Command\ReceiveEmail;
use Externals\Domain\Email\Email;
use Externals\Domain\Email\EmailAddress;
use Externals\Domain\Email\EmailContentParser;
use Externals\Domain\Email\EmailRepository;
use Externals\Domain\Email\EmailSubjectParser;
use Externals\Domain\Thread\ThreadRepository;
use Imapi\Client;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ReceiveEmailHandler
{
    /**
     * @var Client
     */
    private $imapClient;

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var ThreadRepository
     */
    private $threadRepository;

    /**
     * @var EmailRepository
     */
    private $emailRepository;

    /**
     * @var EmailSubjectParser
     */
    private $subjectParser;

    /**
     * @var EmailContentParser
     */
    private $contentParser;

    public function __construct(
        Client $imapClient,
        Connection $db,
        ThreadRepository $threadRepository,
        EmailRepository $emailRepository,
        EmailSubjectParser $subjectParser,
        EmailContentParser $contentParser
    ) {
        $this->imapClient = $imapClient;
        $this->db = $db;
        $this->threadRepository = $threadRepository;
        $this->emailRepository = $emailRepository;
        $this->subjectParser = $subjectParser;
        $this->contentParser = $contentParser;
    }

    public function __invoke(ReceiveEmail $receiveEmail)
    {
        $emailId = $receiveEmail->getEmailId();

        // Check if we have already received the email
        if ($this->emailRepository->contains($emailId)) {
            return;
        }

        $email = $this->imapClient->getEmailFromId($emailId);

        $threadSubject = $this->subjectParser->sanitize($email->getSubject());
        $content = $this->contentParser->parse($email->getTextContent());

        $threadId = $this->threadRepository->findBySubject($threadSubject);
        if (!$threadId) {
            // New thread
            $threadId = $this->threadRepository->create($threadSubject);
        }

        $fromArray = $email->getFrom();
        /** @var \Imapi\EmailAddress $from */
        $from = reset($fromArray);

        $newEmail = new Email(
            $email->getId(),
            $email->getSubject(),
            $content,
            $email->getTextContent(),
            $threadId,
            $email->getDate(),
            new EmailAddress($from->getEmail(), $from->getName())
        );
        $this->emailRepository->add($newEmail);
    }
}
