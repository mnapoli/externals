<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RefreshThread
{
    public function handle(?int $threadNumber = null, ?string $emailId = null): void
    {
        if (($threadNumber === null) === ($emailId === null)) {
            throw new InvalidArgumentException('Pass exactly one of $threadNumber or $emailId');
        }

        $whereClause = $threadNumber !== null ? 'emails.number = ?' : 'emails.id = ?';
        $binding = $threadNumber ?? $emailId;

        DB::statement(<<<SQL
            REPLACE INTO threads (emailId, emailNumber, lastUpdate, emailCount, votes)
              SELECT emails.id, emails.number, MAX(threadEmails.fetchDate), COUNT(threadEmails.id),
                  COALESCE((SELECT SUM(votes.value) FROM votes WHERE votes.emailNumber = emails.number), 0)
              FROM emails
              LEFT JOIN emails threadEmails ON emails.id = threadEmails.threadId
              WHERE emails.isThreadRoot = 1
                AND $whereClause
              GROUP BY emails.id
            SQL, [$binding]);
    }
}
