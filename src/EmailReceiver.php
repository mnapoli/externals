<?php
declare(strict_types = 1);

namespace Externals;

use Doctrine\DBAL\Connection;
use Externals\Domain\Command\ReceiveEmail;
use Externals\Email\Email;
use Externals\Email\EmailAddress;
use Externals\Email\EmailContentParser;
use Externals\Email\EmailRepository;
use Externals\Email\EmailSubjectParser;
use Externals\Thread\ThreadRepository;
use Imapi\Client;
use Psr\Log\LoggerInterface;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class EmailReceiver
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

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Client $imapClient,
        Connection $db,
        ThreadRepository $threadRepository,
        EmailRepository $emailRepository,
        EmailSubjectParser $subjectParser,
        EmailContentParser $contentParser,
        LoggerInterface $logger
    ) {
        $this->imapClient = $imapClient;
        $this->db = $db;
        $this->threadRepository = $threadRepository;
        $this->emailRepository = $emailRepository;
        $this->subjectParser = $subjectParser;
        $this->contentParser = $contentParser;
        $this->logger = $logger;
    }

    public function __invoke(ReceiveEmail $receiveEmail)
    {
        $emailId = $receiveEmail->getEmailId();

        // Check if we have already received the email
        if ($this->emailRepository->contains($emailId)) {
            $this->logger->debug('Skipping email ' . $emailId);
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

        $this->logger->info('New email: ' . $email->getSubject());
    }
}
