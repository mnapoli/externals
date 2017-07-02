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
use Externals\Thread\ThreadRepository;
use PhpMimeMailParser\Parser;
use Psr\Log\LoggerInterface;
use Rvdv\Nntp\Client;
use Rvdv\Nntp\Command\ArticleCommand;
use Rvdv\Nntp\Connection\Connection;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class EmailSynchronizer
{
    /**
     * Some articles that should never
     * be attempted to be fetched.
     */
    const BROKEN_MESSAGES = [992];

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
     * @var SearchIndex
     */
    private $searchIndex;

    public function __construct(
        ThreadRepository $threadRepository,
        EmailRepository $emailRepository,
        EmailSubjectParser $subjectParser,
        EmailContentParser $contentParser,
        SearchIndex $searchIndex,
        LoggerInterface $logger
    ) {
        $this->threadRepository = $threadRepository;
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

    public function synchronizeEmail(int $number, string $rawContent)
    {
        // Check that the string is valid UTF-8, else we cannot store it in database or do anything with it
        if (!mb_check_encoding($rawContent, 'UTF-8')) {
            $this->logger->warning("Cannot synchronize message $number because it contains invalid UTF-8 characters");
            return;
        }

        // For some reason, see https://github.com/madewithlove/why-cant-we-have-nice-things/blob/master/src/Services/Internals/ArticleParser.php
        $rawContent = str_replace("=\n", " =\n", $rawContent);

        $parser = new Parser;
        $parser->setText($rawContent);

        $threadSubject = $this->subjectParser->sanitize($parser->getHeader('subject'));
        $content = $this->contentParser->parse($parser->getMessageBody());

        $threadId = $this->threadRepository->findBySubject($threadSubject);
        if (!$threadId) {
            // New thread
            $threadId = $this->threadRepository->create($threadSubject);
            $this->searchIndex->indexThread($threadId, $threadSubject);
        }

        $emailAddressParser = new EmailAddressParser($parser->getHeader('from'));
        $fromArray = $emailAddressParser->parse();
        /** @var EmailAddress $from */
        $from = reset($fromArray);

        // Reply to
        $inReplyTo = $parser->getHeader('references') ?: null;
        if ($inReplyTo) {
            $inReplyTo = preg_split('/(?<=>)/', $inReplyTo);
            $inReplyTo = array_filter(array_map('trim', $inReplyTo));
            // Take the last item (the direct parent)
            if (! empty($inReplyTo)) {
                $inReplyTo = end($inReplyTo);
            } else {
                $inReplyTo = null;
            }
        }

        $emailId = $parser->getHeader('message-id');
        $newEmail = new Email(
            $emailId,
            $number,
            $content,
            $rawContent,
            $threadId,
            $this->parseDateTime($parser),
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
        $this->searchIndex->indexEmail($newEmail, $threadSubject);

        $this->logger->info('New email: ' . $threadSubject);
    }

    /**
     * @return \DateTimeInterface
     */
    private function parseDateTime(Parser $parser)
    {
        $timezones = [
            'Eastern Daylight Time' => 'EDT',
            'Eastern Standard Time' => 'EST',
            'MET DST' => 'MET',
        ];

        // Try to change timezone to one PHP understands
        $date = strtr($parser->getHeader('date'), $timezones);
        $date = preg_replace('/(.+)\(.+\)$/', '$1', $date);

        $date = new \DateTime($date);
        $date->setTimezone(new DateTimeZone('UTC'));

        return $date;
    }
}
