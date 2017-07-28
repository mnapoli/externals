<?php
declare(strict_types = 1);

namespace Externals;

use DateTimeZone;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Externals\Email\Email;
use Externals\Email\EmailAddress;
use Externals\Email\EmailAddressParser;
use Externals\Email\EmailContentParser;
use Externals\Email\EmailRepository;
use Externals\Email\EmailSubjectParser;
use Externals\Search\SearchIndex;
use Psr\Log\LoggerInterface;
use Rvdv\Nntp\Client;
use Rvdv\Nntp\Command\ArticleCommand;
use Rvdv\Nntp\Connection\Connection;
use Rvdv\Nntp\Exception\UnknownHandlerException;
use ZBateson\MailMimeParser\Header\DateHeader;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class EmailSynchronizer
{
    /**
     * Some articles that should never
     * be attempted to be fetched.
     */
    const BROKEN_MESSAGES = [
        992,
        27418,
        69049,
        69050,
    ];

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
     * @var SearchIndex
     */
    private $searchIndex;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $db;

    public function __construct(
        EmailRepository $emailRepository,
        EmailSubjectParser $subjectParser,
        EmailContentParser $contentParser,
        SearchIndex $searchIndex,
        LoggerInterface $logger,
        \Doctrine\DBAL\Connection $db
    ) {
        $this->emailRepository = $emailRepository;
        $this->subjectParser = $subjectParser;
        $this->contentParser = $contentParser;
        $this->searchIndex = $searchIndex;
        $this->logger = $logger;
        $this->db = $db;
    }

    public function synchronize(int $maxNumberOfEmailsToSynchronize = null)
    {
        $client = new Client(new Connection('news.php.net', 119));
        $client->connect();

        $group = $client->group('php.internals');
        $numberOfLastEmailToSynchronize = (int) $group['last'];
        $numberOfLastEmailSynchronized = $this->emailRepository->getLastEmailNumber();

        $this->logger->info(sprintf(
            '%d emails will be synchronized',
            min($numberOfLastEmailToSynchronize - $numberOfLastEmailSynchronized, $maxNumberOfEmailsToSynchronize)
        ));

        $count = 0;
        for ($number = $numberOfLastEmailSynchronized + 1; $number <= $numberOfLastEmailToSynchronize; $number++) {
            $count++;

            if (in_array($number, self::BROKEN_MESSAGES)) {
                $this->logger->warning("Skipping blacklisted message $number");
                continue;
            }

            $this->logger->info("Synchronizing message $number");

            try {
                $rawContent = $client->sendCommand(new ArticleCommand($number));
            } catch (UnknownHandlerException $e) {
                // Some messages seem to trigger errors on the news server and we cannot fetch them
                $this->logger->warning("Cannot fetch message $number, skipping");
                continue;
            }

            $this->synchronizeEmail($number, $rawContent);

            if (($maxNumberOfEmailsToSynchronize) !== null && ($count >= $maxNumberOfEmailsToSynchronize)) break;
        }

        $client->disconnect();

        // Refresh threads
        if ($count > 0) {
            $this->emailRepository->refreshThreads();
        }
    }

    public function synchronizeEmail(int $number, string $source)
    {
        // Check that the string is valid UTF-8, else we cannot store it in database or do anything with it
        if (!mb_check_encoding($source, 'UTF-8')) {
            $this->logger->warning("Cannot synchronize message $number because it contains invalid UTF-8 characters");
            return;
        }

        $mailParser = new MailMimeParser();
        $parsedDocument = $mailParser->parse($source);

        $subject = $this->subjectParser->sanitize($parsedDocument->getHeaderValue('subject'));
        $content = $this->contentParser->parse((string) $parsedDocument->getTextContent());

        // We don't use the special AddressHeader class because it doesn't seem to parse the
        // person's name at all
        $fromHeader = $parsedDocument->getHeader('from');
        if (!$fromHeader) {
            $this->logger->warning("Cannot synchronize message $number because it contains no 'from' header");
            return;
        }
        $emailAddressParser = new EmailAddressParser($fromHeader->getRawValue());
        $fromArray = $emailAddressParser->parse();
        /** @var EmailAddress $from */
        $from = reset($fromArray);

        $emailId = $parsedDocument->getHeaderValue('message-id');

        // Reply to
        $threadId = $emailId;
        $inReplyTo = null;
        $references = $parsedDocument->getHeaderValue('references');
        if ($references) {
            $references = preg_split('/(?<=>)/', $references);
            $references = array_filter(array_map('trim', $references));
            // Take the last item (the direct parent)
            if (! empty($references)) {
                $threadId = reset($references);
                $inReplyTo = end($references);
            }
        }

        $date = $this->parseDateTime($parsedDocument);
        if (!$date) {
            $this->logger->warning("Cannot synchronize message $number because it contains an invalid date");
            return;
        }

        $newEmail = new Email(
            $emailId,
            $number,
            $subject,
            $content,
            $source,
            $threadId,
            $date,
            $from,
            $inReplyTo
        );

        $this->db->transactional(function () use ($newEmail) {
            try {
                $this->emailRepository->add($newEmail);
            } catch (UniqueConstraintViolationException $e) {
                // For some reason the email ID was already used...
                $this->logger->warning("Cannot synchronize message {$newEmail->getNumber()} because the email ID {$newEmail->getId()} already exists in database");
                return;
            }
            // Index in Algolia
            $this->searchIndex->indexEmail($newEmail);
        });
    }

    /**
     * @return \DateTimeInterface|null
     */
    private function parseDateTime(Message $parsedDocument)
    {
        $dateHeader = $parsedDocument->getHeader('date');

        $date = null;
        if ($dateHeader instanceof DateHeader) {
            $date = $dateHeader->getDateTime();
        }
        // Some dates cannot be parsed using the standard format, for example "13 Mar 2003 12:44:07 -0500"
        try {
            $date = $date ?: new \DateTime($dateHeader->getValue());
        } catch (\Exception $e) {
            // Some dates cannot be parsed
            return null;
        }

        // We store all the dates in UTC
        $date->setTimezone(new DateTimeZone('UTC'));

        return $date;
    }
}
