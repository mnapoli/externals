<?php

declare(strict_types=1);

namespace App\Email;

use App\Exceptions\NotFoundException;
use App\Models\User;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

class EmailRepository
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    public function getById(string $id): Email
    {
        $row = $this->db->selectOne('SELECT * FROM emails WHERE id = ?', [$id]);
        if (! $row) {
            throw new NotFoundException("Email $id was not found");
        }

        return $this->emailFromRow((array) $row);
    }

    public function getByNumber(int $number): Email
    {
        $row = $this->db->selectOne('SELECT * FROM emails WHERE number = ?', [$number]);
        if (! $row) {
            throw new NotFoundException("Email $number was not found");
        }

        return $this->emailFromRow((array) $row);
    }

    /**
     * Returns a threaded view of the emails.
     *
     * @return ThreadItem[]
     */
    public function getThreadView(Email $email, ?User $user = null): array
    {
        // Use $email->threadId rather than ->id: we may not always have the thread root
        $threadId = $email->threadId;

        if ($user) {
            $rows = $this->db->select(
                'SELECT emails.*, IF(readStatus.lastReadDate > emails.fetchDate, 1, 0) as wasRead
                 FROM emails
                 LEFT JOIN user_emails_read readStatus
                    ON readStatus.emailId = ? AND readStatus.userId = ?
                 WHERE emails.threadId = ?
                 ORDER BY emails.date ASC',
                [$threadId, $user->id, $threadId]
            );
        } else {
            $rows = $this->db->select(
                'SELECT emails.*, 0 as wasRead
                 FROM emails
                 WHERE emails.threadId = ?
                 ORDER BY emails.date ASC',
                [$threadId]
            );
        }

        /** @var Email[] $emails */
        $emails = array_map(fn ($row) => $this->emailFromRow((array) $row), $rows);

        /** @var ThreadItem[] $indexedThreadItem */
        $indexedThreadItem = [];
        foreach ($emails as $threadEmail) {
            $indexedThreadItem[$threadEmail->id] = new ThreadItem($threadEmail);
        }

        $rootItems = [];
        foreach ($indexedThreadItem as $item) {
            $replyId = $item->email->inReplyTo;
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
        return $this->findThreads("WHERE threadInfos.subject LIKE '%RFC%'", 'ORDER BY threadInfos.date DESC', 1, null);
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
                    (SELECT votes.value FROM votes WHERE votes.emailNumber = threadInfos.number AND votes.userId = ?) as userVote
                FROM threads
                LEFT JOIN emails threadInfos ON threads.emailId = threadInfos.id
                LEFT JOIN user_emails_read readStatus ON threads.emailId = readStatus.emailId AND readStatus.userId = ?
                $where
                $orderBy
                LIMIT 20 OFFSET $offset
                SQL;
            $parameters = [$user->id, $user->id];
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
            $parameters = [];
        }

        return array_map(fn ($row) => (array) $row, $this->db->select($query, $parameters));
    }

    /**
     * @return Email[]
     */
    public function findLatest(int $since): array
    {
        $rows = $this->db->select(
            'SELECT * FROM emails WHERE number > ? ORDER BY number DESC LIMIT 100',
            [$since]
        );

        return array_map(fn ($row) => $this->emailFromRow((array) $row), $rows);
    }

    public function add(Email $email): void
    {
        DB::table('emails')->insert([
            'id' => $email->id,
            'number' => $email->number,
            'subject' => $email->subject,
            'content' => $email->content,
            'source' => $email->source,
            'threadId' => $email->threadId,
            'isThreadRoot' => $email->isThreadRoot(),
            'date' => $email->date->format('Y-m-d H:i:s'),
            'fetchDate' => (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
            'fromEmail' => $email->from->email,
            'fromName' => $email->from->name,
            'inReplyTo' => $email->inReplyTo,
        ]);
    }

    public function getEmailSource(int $number): string
    {
        $row = $this->db->selectOne('SELECT source FROM emails WHERE `number` = ?', [$number]);
        if (! $row) {
            throw new NotFoundException('Email not found');
        }

        return ((array) $row)['source'];
    }

    public function getLastEmailNumber(): int
    {
        $row = $this->db->selectOne('SELECT MAX(number) AS max FROM emails');

        return (int) ($row->max ?? 0);
    }

    public function updateContent(string $emailId, string $newContent): void
    {
        DB::table('emails')->where('id', $emailId)->update(['content' => $newContent]);
    }

    public function getEmailCount(): int
    {
        return (int) DB::table('emails')->count();
    }

    public function getThreadCount(): int
    {
        return (int) DB::table('emails')->where('isThreadRoot', 1)->count();
    }

    /**
     * Find the latest thread that matches the given subject.
     */
    public function findBySubject(string $subject): Email
    {
        $row = $this->db->selectOne(
            'SELECT * FROM emails WHERE subject = ? AND isThreadRoot = 1 ORDER BY date DESC LIMIT 1',
            [$subject]
        );
        if (! $row) {
            throw new NotFoundException('Email not found');
        }

        return $this->emailFromRow((array) $row);
    }

    public function markAsRead(Email $email, User $user): void
    {
        // UTC_TIMESTAMP() so the stored time is in UTC regardless of MySQL server timezone
        $this->db->statement(
            'REPLACE INTO user_emails_read (emailId, userId, lastReadDate) VALUES (?, ?, UTC_TIMESTAMP())',
            [$email->id, $user->id]
        );
    }

    /**
     * Refresh the projection of all threads.
     */
    public function refreshThreads(): void
    {
        $this->db->statement(<<<'SQL'
            REPLACE INTO threads (emailId, emailNumber, lastUpdate, emailCount, votes)
              SELECT emails.id, emails.number, MAX(threadEmails.fetchDate), COUNT(threadEmails.id),
                  COALESCE((SELECT SUM(votes.value) FROM votes WHERE votes.emailNumber = emails.number), 0)
              FROM emails
              LEFT JOIN emails threadEmails ON emails.id = threadEmails.threadId
              WHERE emails.isThreadRoot = 1
              GROUP BY emails.id
            SQL);
    }

    /**
     * Refresh the projection of a single thread.
     */
    public function refreshThread(int $threadNumber): void
    {
        $this->db->statement(<<<'SQL'
            REPLACE INTO threads (emailId, emailNumber, lastUpdate, emailCount, votes)
              SELECT emails.id, emails.number, MAX(threadEmails.fetchDate), COUNT(threadEmails.id),
                  COALESCE((SELECT SUM(votes.value) FROM votes WHERE votes.emailNumber = emails.number), 0)
              FROM emails
              LEFT JOIN emails threadEmails ON emails.id = threadEmails.threadId
              WHERE emails.isThreadRoot = 1
                AND emails.number = ?
              GROUP BY emails.id
            SQL, [$threadNumber]);
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
            new EmailAddress($row['fromEmail'] ?? null, $row['fromName'] ?? null),
            $row['inReplyTo'] ?? null,
        );

        if (! empty($row['wasRead'])) {
            $email->isRead = true;
        }

        return $email;
    }
}
