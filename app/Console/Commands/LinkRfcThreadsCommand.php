<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Rfc\RfcThreads;
use Illuminate\Console\Command;

/**
 * New threads are linked to RFCs when they are synchronized: this command
 * links the threads that existed before.
 */
class LinkRfcThreadsCommand extends Command
{
    protected $signature = 'externals:link-rfc-threads';
    protected $description = 'Link existing threads to the RFCs their root email links to';

    public function handle(RfcThreads $threads): int
    {
        $count = $threads->linkAllThreadRoots();

        $this->comment("$count links between threads and RFCs created");

        return self::SUCCESS;
    }
}
