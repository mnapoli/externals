<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\RefreshAllThreads;
use Illuminate\Console\Command;

class RefreshThreadsCommand extends Command
{
    protected $signature = 'externals:refresh-threads';

    protected $description = 'Refresh the threads projection';

    public function handle(): int
    {
        $start = microtime(true);
        app(RefreshAllThreads::class)->handle();
        $time = microtime(true) - $start;

        $this->comment(sprintf('Threads have been refreshed in %.2f seconds', $time));

        return self::SUCCESS;
    }
}
