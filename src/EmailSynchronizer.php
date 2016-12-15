<?php
declare(strict_types = 1);

namespace Externals;

use Externals\Email\Email;
use Externals\Email\EmailAddress;
use Externals\Email\EmailContentParser;
use Externals\Email\EmailRepository;
use Externals\Email\EmailSearchIndex;
use Externals\Email\EmailSubjectParser;
use Externals\Thread\ThreadRepository;
use Imapi\Client;
use Imapi\Query\QueryBuilder;
use Psr\Log\LoggerInterface;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class EmailSynchronizer
{
    /**
     * @var Client
     */
    private $imapClient;

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

    /**
     * @var EmailSearchIndex
     */
    private $searchIndex;

    public function __construct(
        Client $imapClient,
        ThreadRepository $threadRepository,
        EmailRepository $emailRepository,
        EmailSubjectParser $subjectParser,
        EmailContentParser $contentParser,
        EmailSearchIndex $searchIndex,
        LoggerInterface $logger
    ) {
        $this->imapClient = $imapClient;
        $this->threadRepository = $threadRepository;
        $this->emailRepository = $emailRepository;
        $this->subjectParser = $subjectParser;
        $this->contentParser = $contentParser;
        $this->searchIndex = $searchIndex;
        $this->logger = $logger;
    }

    public function synchronize(int $days)
    {
        $query = QueryBuilder::create('INBOX')
            ->youngerThan(3600 * 24 * $days)
            ->getQuery();
        $emailIds = $this->imapClient->getEmailIds($query);

        $this->logger->info(sprintf('%d emails to synchronize over the last %d day(s)', count($emailIds), $days));

        foreach ($emailIds as $emailId) {
            // Check if we have already received the email
            if ($this->emailRepository->contains((string) $emailId)) {
                $this->logger->debug('Skipping email ' . $emailId);
                continue;
            }

            $this->fetchEmail((string) $emailId);
        }
    }

    public function fetchEmail(string $emailId)
    {
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
            $email->getUid(),
            $email->getSubject(),
            $content,
            $email->getTextContent(),
            $threadId,
            $email->getDate(),
            new EmailAddress($from->getEmail(), $from->getName()),
            $email->getMessageId(),
            $email->getInReplyTo()
        );
        $this->emailRepository->add($newEmail);

        // Index in Algolia
        $this->searchIndex->indexEmail($newEmail);

        $this->logger->info('New email: ' . $email->getSubject());
    }
}
