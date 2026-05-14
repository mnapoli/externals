<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\DB;

class RefreshAllThreads
{
    public function handle(): void
    {
        DB::statement(<<<'SQL'
            REPLACE INTO threads (emailId, emailNumber, lastUpdate, emailCount, votes)
              SELECT emails.id, emails.number, MAX(threadEmails.fetchDate), COUNT(threadEmails.id),
                  COALESCE((SELECT SUM(votes.value) FROM votes WHERE votes.emailNumber = emails.number), 0)
              FROM emails
              LEFT JOIN emails threadEmails ON emails.id = threadEmails.threadId
              WHERE emails.isThreadRoot = 1
              GROUP BY emails.id
            SQL);
    }
}
