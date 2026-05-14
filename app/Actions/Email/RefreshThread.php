<?php

declare(strict_types=1);

namespace App\Actions\Email;

use Illuminate\Support\Facades\DB;

class RefreshThread
{
    public function handle(int $threadNumber): void
    {
        DB::statement(<<<'SQL'
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
}
