<?php

declare(strict_types=1);

namespace App\Email;

use App\Exceptions\NotFoundException;
use App\Nntp\ArticleNotFoundException;
use App\Nntp\NntpClient;
use App\Search\SearchIndex;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Psr\Log\LoggerInterface;
use Throwable;
use ZBateson\MailMimeParser\Header\DateHeader;
use ZBateson\MailMimeParser\IMessage;
use ZBateson\MailMimeParser\MailMimeParser;

class EmailSynchronizer
{
    /**
     * Articles that should never be attempted to be fetched.
     */
    public const array BROKEN_MESSAGES = [
        992,
        27418,
        69049,
        69050,
        // See https://github.com/mnapoli/externals/issues/173
        117903,
        // See https://github.com/mnapoli/externals/issues/191
        121607,
    ];

    public function __construct(
        private readonly EmailRepository $emailRepository,
        private readonly EmailSubjectParser $subjectParser,
        private readonly EmailContentParser $contentParser,
        private readonly SearchIndex $searchIndex,
        private readonly LoggerInterface $logger,
        private readonly ConnectionInterface $db,
    ) {}

    public function synchronize(?int $maxNumberOfEmailsToSynchronize = null): void
    {
        $client = new NntpClient('news.php.net', 119);
        $client->connect();

        try {
            $group = $client->group('php.internals');
            $numberOfLastEmailToSynchronize = $group['last'];
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

                if (in_array($number, self::BROKEN_MESSAGES, true)) {
                    $this->logger->warning("Skipping blacklisted message $number");

                    continue;
                }

                $this->logger->info("Synchronizing message $number");

                try {
                    $rawContent = $client->article($number);
                } catch (ArticleNotFoundException) {
                    $this->logger->warning("Cannot fetch message $number, skipping");

                    continue;
                }

                $this->synchronizeEmail($number, $rawContent);

                if ($maxNumberOfEmailsToSynchronize !== null && $count >= $maxNumberOfEmailsToSynchronize) {
                    break;
                }
            }
        } finally {
            $client->disconnect();
        }

        if (($count ?? 0) > 0) {
            $this->emailRepository->refreshThreads();
        }
    }

    public function synchronizeEmail(int $number, string $source): void
    {
        if (! mb_check_encoding($source, 'UTF-8')) {
            $this->logger->warning("Cannot synchronize message $number because it contains invalid UTF-8 characters");

            return;
        }

        $mailParser = new MailMimeParser;
        $parsedDocument = $mailParser->parse($source, false);

        $subject = $this->subjectParser->sanitize((string) $parsedDocument->getHeaderValue('subject'));
        $content = $this->contentParser->parse((string) $parsedDocument->getTextContent());

        $fromHeader = $parsedDocument->getHeader('from');
        if (! $fromHeader) {
            $this->logger->warning("Cannot synchronize message $number because it contains no 'from' header");

            return;
        }
        $fromArray = (new EmailAddressParser($fromHeader->getRawValue()))->parse();
        $from = reset($fromArray);

        $emailId = $parsedDocument->getHeaderValue('message-id');

        $inReplyTo = null;
        $inReplyToHeader = $parsedDocument->getHeaderValue('In-Reply-To');
        if ($inReplyToHeader) {
            $inReplyToHeader = preg_split('/(?<=>)/', $inReplyToHeader);
            $inReplyToHeader = array_filter(array_map('trim', $inReplyToHeader));
            if (! empty($inReplyToHeader)) {
                $inReplyTo = reset($inReplyToHeader);
            }
        }

        $firstReference = null;
        $references = $parsedDocument->getHeaderValue('References');
        if ($references) {
            $references = preg_split('/(?<=>)/', $references);
            $references = array_filter(array_map('trim', $references));
            if (! empty($references)) {
                $firstReference = reset($references);
                if (! $inReplyTo) {
                    // In old mails the In-Reply-To header didn't exist; instead it was at the end of the references.
                    // Example: https://externals.io/message/2536#2784
                    $inReplyTo = end($references);
                }
            }
        }

        $threadId = null;
        if ($firstReference !== null) {
            // iPhone Mail clients may omit the root email from References — see https://github.com/mnapoli/externals/pull/189/files
            $threadId = $this->findEmailThreadId($firstReference);
        } elseif ($inReplyTo !== null) {
            $threadId = $this->findEmailThreadId($inReplyTo);
        }

        $threadId ??= $emailId;

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
            $inReplyTo,
        );

        $this->db->transaction(function () use ($newEmail): void {
            try {
                $this->emailRepository->add($newEmail);
            } catch (QueryException $e) {
                // Duplicate key — email ID was already used
                if ($e->getCode() === '23000') {
                    $this->logger->warning("Cannot synchronize message {$newEmail->number} because the email ID {$newEmail->id} already exists in database");

                    return;
                }
                throw $e;
            }
            $this->searchIndex->indexEmail($newEmail);
        });
    }

    private function parseDateTime(IMessage $parsedDocument): ?DateTimeInterface
    {
        $dateHeader = $parsedDocument->getHeader('date');

        $date = null;
        if ($dateHeader instanceof DateHeader) {
            $date = $dateHeader->getDateTime();
        }
        try {
            $date = $date ?: new DateTime($dateHeader->getValue());
        } catch (Throwable) {
            return null;
        }

        $date->setTimezone(new DateTimeZone('UTC'));

        return $date;
    }

    private function findEmailThreadId(string $targetId): ?string
    {
        try {
            $email = $this->emailRepository->getById($targetId);
        } catch (NotFoundException) {
            return null;
        }

        // If threadId is set, this email is inside a thread (but not the root)
        if ($email->threadId) {
            return $email->threadId;
        }

        // Otherwise this email is itself the thread root
        return $email->id;
    }
}
