<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Models\Email;
use App\Models\User;
use App\Support\Email\ThreadItem;
use App\Support\Email\ThreadSummary;
use Illuminate\Support\Facades\DB;

/**
 * Holds the multi-join thread aggregation queries that don't translate
 * cleanly to Eloquent. Returns ThreadSummary value objects for thread
 * listings and Email model instances for the threaded view.
 */
class ThreadQuery
{
    /**
     * @return ThreadSummary[]
     */
    public function findLatestThreads(int $page, ?User $user): array
    {
        return $this->findThreads('', 'ORDER BY threads.lastUpdate DESC', $page, $user);
    }

    /**
     * @return ThreadSummary[]
     */
    public function findTopThreads(int $page, ?User $user): array
    {
        $where = 'WHERE threads.votes > 0 AND threads.lastUpdate > DATE_SUB(NOW(), INTERVAL 1 MONTH)';

        return $this->findThreads($where, 'ORDER BY threads.votes DESC, threads.lastUpdate DESC', $page, $user);
    }

    /**
     * @return ThreadSummary[]
     */
    public function findLatestRfcThreads(): array
    {
        return $this->findThreads("WHERE threadInfos.subject LIKE '%RFC%'", 'ORDER BY threadInfos.date DESC', 1, null);
    }

    /**
     * Returns a threaded view of the emails as a tree of ThreadItem.
     *
     * @return ThreadItem[]
     */
    public function getThreadView(Email $email, ?User $user = null): array
    {
        // Use $email->threadId rather than ->id: we may not always have the thread root
        $threadId = $email->threadId;

        if ($user) {
            $rows = DB::select(
                'SELECT emails.*, IF(readStatus.lastReadDate > emails.fetchDate, 1, 0) as wasRead
                 FROM emails
                 LEFT JOIN user_emails_read readStatus
                    ON readStatus.emailId = ? AND readStatus.userId = ?
                 WHERE emails.threadId = ?
                 ORDER BY emails.date ASC',
                [$threadId, $user->id, $threadId],
            );
        } else {
            $rows = DB::select(
                'SELECT emails.*, 0 as wasRead
                 FROM emails
                 WHERE emails.threadId = ?
                 ORDER BY emails.date ASC',
                [$threadId],
            );
        }

        $emails = Email::hydrate(array_map(fn($row) => (array) $row, $rows));

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

    /**
     * @return ThreadSummary[]
     */
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
                    threadInfos.fromEmail,
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

        return array_map(ThreadSummary::fromRow(...), DB::select($query, $parameters));
    }
}
