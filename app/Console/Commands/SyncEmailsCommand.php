<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Email\EmailSynchronizer;
use Illuminate\Console\Command;

class SyncEmailsCommand extends Command
{
    protected $signature = 'externals:sync {max? : Max number of emails to synchronize}';
    protected $description = 'Synchronize emails from the PHP internals NNTP server';

    public function handle(EmailSynchronizer $synchronizer): int
    {
        $start = microtime(true);

        $max = $this->argument('max');
        $synchronizer->synchronize($max !== null ? (int) $max : null);

        $time = microtime(true) - $start;
        $this->comment(sprintf('Emails have been synchronized in %.2f seconds', $time));

        return self::SUCCESS;
    }
}
