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
use Psr\Log\LoggerInterface;
use Rvdv\Nntp\Client;
use Rvdv\Nntp\Command\ArticleCommand;
use Rvdv\Nntp\Connection\Connection;
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
        1023,
        1024,
        1025,
        1026,
        1027,
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

    public function __construct(
        EmailRepository $emailRepository,
        EmailSubjectParser $subjectParser,
        EmailContentParser $contentParser,
        SearchIndex $searchIndex,
        LoggerInterface $logger
    ) {
        $this->emailRepository = $emailRepository;
        $this->subjectParser = $subjectParser;
        $this->contentParser = $contentParser;
        $this->searchIndex = $searchIndex;
        $this->logger = $logger;
    }

    public function synchronize(int $maxNumberOfEmailsToSynchronize)
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
                $this->logger->warning("Skipping broken message $number");
                continue;
            }

            $rawContent = $client->sendCommand(new ArticleCommand($number));

            $this->synchronizeEmail($number, $rawContent);

            if ($count >= $maxNumberOfEmailsToSynchronize) break;
        }

        $client->disconnect();
    }

    public function synchronizeEmail(int $number, string $source)
    {
        // Check that the string is valid UTF-8, else we cannot store it in database or do anything with it
        if (!mb_check_encoding($source, 'UTF-8')) {
            $this->logger->warning("Cannot synchronize message $number because it contains invalid UTF-8 characters");
            return;
        }

        // For some reason, see https://github.com/madewithlove/why-cant-we-have-nice-things/blob/master/src/Services/Internals/ArticleParser.php
        // Temporarily remove, we'll see if we really need this...
//        $source = str_replace("=\n", " =\n", $source);

        $mailParser = new MailMimeParser();
        $parsedDocument = $mailParser->parse($source);

        $subject = $this->subjectParser->sanitize($parsedDocument->getHeaderValue('subject'));
        $content = $this->contentParser->parse($parsedDocument->getTextContent());

        // We don't use the special AddressHeader class because it doesn't seem to parse the
        // person's name at all
        $emailAddressParser = new EmailAddressParser($parsedDocument->getHeader('from')->getRawValue());
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

        $newEmail = new Email(
            $emailId,
            $number,
            $subject,
            $content,
            $source,
            $threadId,
            $this->parseDateTime($parsedDocument),
            $from,
            $inReplyTo
        );
        try {
            $this->emailRepository->add($newEmail);
        } catch (UniqueConstraintViolationException $e) {
            // For some reason the email ID was already used...
            $this->logger->warning("Cannot synchronize message $number because the email ID $emailId already exists in database");
            return;
        }

        // Index in Algolia
        $this->searchIndex->indexEmail($newEmail);

        $this->logger->info('New email: ' . $subject);
    }

    private function parseDateTime(Message $parsedDocument) : \DateTimeInterface
    {
        $dateHeader = $parsedDocument->getHeader('date');

        $date = null;
        if ($dateHeader instanceof DateHeader) {
            $date = $dateHeader->getDateTime();
        }
        // Some dates cannot be parsed using the standard format, for example "13 Mar 2003 12:44:07 -0500"
        $date = $date ?: new \DateTime($dateHeader->getValue());

        // We store all the dates in UTC
        $date->setTimezone(new DateTimeZone('UTC'));

        return $date;
    }
}
