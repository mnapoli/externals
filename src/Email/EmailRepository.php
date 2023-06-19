<?php declare(strict_types=1);

namespace Externals\Email;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Externals\NotFound;
use Externals\User\User;

class EmailRepository
{
    public function __construct(
        private Connection $db
    ) {
    }

    public function getById(string $id): Email
    {
        $row = $this->db->fetchAssociative('SELECT * FROM emails WHERE id = ?', [$id]);
        if (! $row) {
            throw new NotFound("Email $id was not found");
        }
        return $this->emailFromRow($row);
    }

    public function getByNumber(int $number): Email
    {
        $row = $this->db->fetchAssociative('SELECT * FROM emails WHERE number = ?', [$number], ['integer']);
        if (! $row) {
            throw new NotFound("Email $number was not found");
        }
        return $this->emailFromRow($row);
    }

    /**
     * Returns a threaded view of the emails.
     *
     * @return ThreadItem[]
     */
    public function getThreadView(Email $email, ?User $user = null): array
    {
        $qb = $this->db->createQueryBuilder();
        $qb->select('emails.*')
            ->from('emails')
            ->where('emails.threadId = :threadId')
            ->orderBy('emails.date', 'ASC')
            // We use getThreadId() instead of getId() because we may not always have the thread
            // root here (sometimes we cannot find the thread root, yet we can still show the thread)
            ->setParameter('threadId', $email->getThreadId());

        if ($user) {
            $qb->addSelect('IF(readStatus.lastReadDate > emails.fetchDate, 1, 0) as wasRead')
                ->leftJoin('emails', 'user_emails_read', 'readStatus', 'readStatus.emailId = :threadId AND readStatus.userId = :userId');
            $qb->setParameter('userId', $user->id);
        } else {
            $qb->addSelect('0 as wasRead');
        }

        /** @var Email[] $emails */
        $emails = array_map(
            [$this, 'emailFromRow'],
            $qb->execute()->fetchAllAssociative()
        );

        // Index by ID
        /** @var ThreadItem[] $indexedThreadItem */
        $indexedThreadItem = [];
        foreach ($emails as $threadEmail) {
            $indexedThreadItem[$threadEmail->getId()] = new ThreadItem($threadEmail);
        }

        // Link each email to the one it replies to
        $rootItems = [];
        foreach ($indexedThreadItem as $item) {
            $replyId = $item->getEmail()->getInReplyTo();
            if ($replyId && isset($indexedThreadItem[$replyId])) {
                $indexedThreadItem[$replyId]->addReply($item);
            } else {
                $rootItems[] = $item;
            }
        }

        return $rootItems;
    }

    public function findLatestThreads(int $page, ?User $user): array
    {
        return $this->findThreads('', 'ORDER BY threads.lastUpdate DESC', $page, $user);
    }

    public function findTopThreads(int $page, ?User $user): array
    {
        $where = 'WHERE threads.votes > 0 AND threads.lastUpdate > DATE_SUB(NOW(), INTERVAL 1 MONTH)';
        return $this->findThreads($where, 'ORDER BY threads.votes DESC, threads.lastUpdate DESC', $page, $user);
    }

    public function findLatestRfcThreads(): array
    {
        return $this->findThreads("where threadInfos.subject like '%RFC%'", 'order by threadInfos.date desc', 1, null);
    }

    private function findThreads(string $where, string $orderBy, int $page, ?User $user): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * 20;

        if ($user) {
            $query = <<<SQL
            SELECT
                threads.emailNumber as number,
                threadInfos.subject,
                threadInfos.date,
                threadInfos.fromName,
                threadInfos.fromEmail,
                threads.emailCount,
                threads.lastUpdate,
                threads.votes,
                IF(readStatus.lastReadDate AND readStatus.lastReadDate >= threads.lastUpdate, 1, 0) as isRead,
                (SELECT votes.value FROM votes WHERE votes.emailNumber = threadInfos.number AND votes.userId = :userId) as userVote
            FROM threads
            LEFT JOIN emails threadInfos ON threads.emailId = threadInfos.id
            LEFT JOIN user_emails_read readStatus ON threads.emailId = readStatus.emailId AND readStatus.userId = :userId
            $where
            $orderBy
            LIMIT 20 OFFSET $offset
            SQL;
            $parameters = [
                'userId' => $user->id,
            ];
        } else {
            $query = <<<SQL
            SELECT
                threads.emailNumber as number,
                threadInfos.subject,
                threadInfos.date,
                threadInfos.fromName,
                threads.emailCount,
                threads.lastUpdate,
                threads.votes,
                0 as isRead,
                NULL as userVote
            FROM threads
            LEFT JOIN emails threadInfos ON threads.emailId = threadInfos.id
            $where
            $orderBy
            LIMIT 20 OFFSET $offset
            SQL;
        }

        return $this->db->fetchAllAssociative($query, $parameters ?? []);
    }

    /**
     * @return Email[]
     */
    public function findLatest(int $since): array
    {
        $qb = $this->db->createQueryBuilder();
        $qb->select('*')
            ->from('emails')
            ->where('number > :since')
            ->orderBy('number', 'DESC')
            ->setMaxResults(100)
            ->setParameter('since', $since);

        return array_map([$this, 'emailFromRow'], $qb->execute()->fetchAllAssociative());
    }

    public function add(Email $email): void
    {
        $this->db->insert('emails', [
            'id' => $email->getId(),
            'number' => $email->getNumber(),
            'subject' => $email->getSubject(),
            'content' => $email->getContent(),
            'source' => $email->getSource(),
            'threadId' => $email->getThreadId(),
            'isThreadRoot' => $email->isThreadRoot(),
            'date' => $email->getDate(),
            'fetchDate' => new DateTime('now', new DateTimeZone('UTC')),
            'fromEmail' => $email->getFrom()->getEmail(),
            'fromName' => $email->getFrom()->getName(),
            'inReplyTo' => $email->getInReplyTo(),
        ], [
            'string',
            'integer',
            'text',
            'text',
            'text',
            'string',
            'boolean',
            'datetime',
            'datetime',
            'string',
            'string',
            'string',
        ]);
    }

    public function getEmailSource(int $number): string
    {
        $email = $this->db->fetchAssociative('SELECT * FROM emails WHERE `number` = ?', [$number]);
        if (! $email) {
            throw new NotFound('Email not found');
        }
        $email = $this->emailFromRow($email);
        return $email->getSource();
    }

    public function getLastEmailNumber(): int
    {
        return (int) $this->db->fetchOne('SELECT MAX(number) FROM emails');
    }

    public function updateContent(string $emailId, string $newContent): void
    {
        $this->db->update('emails', [
            'content' => $newContent,
        ], ['id' => $emailId]);
    }

    public function getEmailCount(): int
    {
        return (int) $this->db->fetchOne('SELECT COUNT(*) FROM emails');
    }

    public function getThreadCount(): int
    {
        return (int) $this->db->fetchOne('SELECT COUNT(*) FROM emails WHERE isThreadRoot = 1');
    }

    /**
     * Find the latest thread that matches the given subject.
     */
    public function findBySubject(string $subject): Email
    {
        $row = $this->db->fetchAssociative('SELECT * FROM emails WHERE subject = ? AND isThreadRoot = 1 ORDER BY date DESC LIMIT 1', [$subject]);
        if (! $row) {
            throw new NotFound('Email not found');
        }
        return $this->emailFromRow($row);
    }

    public function markAsRead(Email $email, User $user): void
    {
        // Make sure to set the time in UTC using `UTC_TIMESTAMP()`
        $this->db->executeQuery('REPLACE INTO user_emails_read (emailId, userId, lastReadDate) VALUES (?, ?, UTC_TIMESTAMP())', [
            $email->getId(),
            $user->id,
        ]);
    }

    /**
     * Refresh the projection of all threads.
     */
    public function refreshThreads(): void
    {
        $query = <<<'SQL'
        REPLACE INTO threads (emailId, emailNumber, lastUpdate, emailCount, votes)
          SELECT emails.id, emails.number, MAX(threadEmails.fetchDate), COUNT(threadEmails.id),
              COALESCE((SELECT SUM(votes.value) FROM votes WHERE votes.emailNumber = emails.number), 0)
          FROM emails
          LEFT JOIN emails threadEmails ON emails.id = threadEmails.threadId
          WHERE emails.isThreadRoot = 1
          GROUP BY emails.id
        SQL;
        $this->db->executeQuery($query);
    }

    /**
     * Refresh the projection of a single thread.
     */
    public function refreshThread(int $threadNumber): void
    {
        $query = <<<'SQL'
        REPLACE INTO threads (emailId, emailNumber, lastUpdate, emailCount, votes)
          SELECT emails.id, emails.number, MAX(threadEmails.fetchDate), COUNT(threadEmails.id),
              COALESCE((SELECT SUM(votes.value) FROM votes WHERE votes.emailNumber = emails.number), 0)
          FROM emails
          LEFT JOIN emails threadEmails ON emails.id = threadEmails.threadId
          WHERE emails.isThreadRoot = 1
            AND emails.number = ?
          GROUP BY emails.id
        SQL;
        $this->db->executeQuery($query, [$threadNumber]);
    }

    private function emailFromRow(array $row): Email
    {
        $date = $row['date'];
        if (is_string($date)) {
            $date = new DateTimeImmutable($date);
        }

        $email = new Email(
            $row['id'],
            (int) $row['number'],
            $row['subject'],
            $row['content'],
            $row['source'],
            $row['threadId'],
            $date,
            new EmailAddress($row['fromEmail'], $row['fromName']),
            $row['inReplyTo']
        );

        if (array_key_exists('wasRead', $row) && $row['wasRead']) {
            $email->markAsRead();
        }

        return $email;
    }
}
