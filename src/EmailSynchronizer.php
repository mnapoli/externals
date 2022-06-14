<?php declare(strict_types=1);

namespace Externals;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Externals\Email\Email;
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
use Throwable;
use ZBateson\MailMimeParser\Header\DateHeader;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message;

class EmailSynchronizer
{
    /**
     * Some articles that should never
     * be attempted to be fetched.
     */
    public const BROKEN_MESSAGES = [
        992,
        27418,
        69049,
        69050,
        // See https://github.com/mnapoli/externals/issues/173
        117903,
    ];

    public function __construct(
        private EmailRepository $emailRepository,
        private EmailSubjectParser $subjectParser,
        private EmailContentParser $contentParser,
        private SearchIndex $searchIndex,
        private LoggerInterface $logger,
        private \Doctrine\DBAL\Connection $db
    ) {
    }

    public function synchronize(?int $maxNumberOfEmailsToSynchronize = null): void
    {
        $client = new Client(new Connection('news.php.net', 119));
        $client->connect();

        $group = $client->group('php.internals');
        $numberOfLastEmailToSynchronize = (int) $group['last'];
        $numberOfLastEmailSynchronized = $this->emailRepository->getLastEmailNumber();

        if ($maxNumberOfEmailsToSynchronize !== null) {
            $this->logger->info(sprintf(
                '%d emails will be synchronized',
                min($numberOfLastEmailToSynchronize - $numberOfLastEmailSynchronized, $maxNumberOfEmailsToSynchronize)
            ));
        }

        $count = 0;
        for ($number = $numberOfLastEmailSynchronized + 1; $number <= $numberOfLastEmailToSynchronize; $number++) {
            $count++;

            if (in_array($number, self::BROKEN_MESSAGES)) {
                $this->logger->warning("Skipping blacklisted message $number");
                continue;
            }

            $this->logger->info("Synchronizing message $number");

            try {
                $rawContent = $client->sendCommand(new ArticleCommand((string) $number));
            } catch (UnknownHandlerException) {
                // Some messages seem to trigger errors on the news server and we cannot fetch them
                $this->logger->warning("Cannot fetch message $number, skipping");
                continue;
            }

            $this->synchronizeEmail($number, $rawContent);

            if ($maxNumberOfEmailsToSynchronize !== null && ($count >= $maxNumberOfEmailsToSynchronize)) {
                break;
            }
        }

        $client->disconnect();

        // Refresh threads
        if ($count > 0) {
            $this->emailRepository->refreshThreads();
        }
    }

    public function synchronizeEmail(int $number, string $source): void
    {
        // Check that the string is valid UTF-8, else we cannot store it in database or do anything with it
        if (! mb_check_encoding($source, 'UTF-8')) {
            $this->logger->warning("Cannot synchronize message $number because it contains invalid UTF-8 characters");
            return;
        }

        $mailParser = new MailMimeParser;
        $parsedDocument = $mailParser->parse($source);

        $subject = $this->subjectParser->sanitize($parsedDocument->getHeaderValue('subject'));
        $content = $this->contentParser->parse((string) $parsedDocument->getTextContent());

        // We don't use the special AddressHeader class because it doesn't seem to parse the
        // person's name at all
        $fromHeader = $parsedDocument->getHeader('from');
        if (! $fromHeader) {
            $this->logger->warning("Cannot synchronize message $number because it contains no 'from' header");
            return;
        }
        $emailAddressParser = new EmailAddressParser($fromHeader->getRawValue());
        $fromArray = $emailAddressParser->parse();
        $from = reset($fromArray);

        $emailId = $parsedDocument->getHeaderValue('message-id');

        // Extract the message we're replying to from the "In-Reply-To" header
        $inReplyTo = null;
        $inReplyToHeader = $parsedDocument->getHeaderValue('In-Reply-To');
        if ($inReplyToHeader) {
            $inReplyToHeader = preg_split('/(?<=>)/', $inReplyToHeader);
            $inReplyToHeader = array_filter(array_map('trim', $inReplyToHeader));
            // Take the first item
            if (! empty($inReplyToHeader)) {
                /** @var string $inReplyTo */
                $inReplyTo = reset($inReplyToHeader);
            }
        }
        // Extract the thread ID from the "references" header
        $threadId = null;
        $references = $parsedDocument->getHeaderValue('References');
        if ($references) {
            $references = preg_split('/(?<=>)/', $references);
            $references = array_filter(array_map('trim', $references));
            if (! empty($references)) {
                $threadId = reset($references);
                if (! $inReplyTo) {
                    // In old mails the `In-Reply-To` header didn't exist, instead it was at the end of the references
                    // Example: https://externals.io/message/2536#2784
                    $inReplyTo = end($references);
                }
            }
        }
        // We know it is a reply to an email but we weren't able to find the thread ID: let's find it from our database
        if ($threadId === null && $inReplyTo !== null) {
            $threadId = $this->findEmailThreadId($inReplyTo);
        }
        // No thread ID: this is a new thread
        if ($threadId === null) {
            $threadId = $emailId;
        }

        $date = $this->parseDateTime($parsedDocument);
        if (! $date) {
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

        $this->db->transactional(function () use ($newEmail): void {
            try {
                $this->emailRepository->add($newEmail);
            } catch (UniqueConstraintViolationException) {
                // For some reason the email ID was already used...
                $this->logger->warning("Cannot synchronize message {$newEmail->getNumber()} because the email ID {$newEmail->getId()} already exists in database");
                return;
            }
            // Index in Algolia
            $this->searchIndex->indexEmail($newEmail);
        });
    }

    private function parseDateTime(Message $parsedDocument): ?DateTimeInterface
    {
        $dateHeader = $parsedDocument->getHeader('date');

        $date = null;
        if ($dateHeader instanceof DateHeader) {
            $date = $dateHeader->getDateTime();
            assert($date instanceof DateTime);
        }
        // Some dates cannot be parsed using the standard format, for example "13 Mar 2003 12:44:07 -0500"
        try {
            $date = $date ?: new DateTime($dateHeader->getValue());
        } catch (Throwable) {
            // Some dates cannot be parsed
            return null;
        }

        // We store all the dates in UTC
        $date->setTimezone(new DateTimeZone('UTC'));

        return $date;
    }

    private function findEmailThreadId(string $inReplyTo): ?string
    {
        try {
            $email = $this->emailRepository->getById($inReplyTo);
        } catch (NotFound) {
            // We didn't find the thread, let's move on
            return null;
        }
        // If getThreadId() is not null that means $email is inside a thread (but not the root)
        if ($email->getThreadId()) {
            // Then we return the thread root ID
            return $email->getThreadId();
        }

        // In the other case that means that $email is the thread root
        return $email->getId();
    }
}
