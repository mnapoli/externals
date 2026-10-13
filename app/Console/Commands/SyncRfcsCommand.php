<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Rfc\RfcSynchronizer;
use Illuminate\Console\Command;

class SyncRfcsCommand extends Command
{
    protected $signature = 'externals:sync-rfcs {--max-pages=150 : Max number of RFC pages to download, 0 for no limit}
        {--all : Download all RFC pages, not only the new or changed ones}';
    protected $description = 'Synchronize RFCs from wiki.php.net';

    public function handle(RfcSynchronizer $synchronizer): int
    {
        $start = microtime(true);

        $maxPages = (int) $this->option('max-pages');
        $result = $synchronizer->synchronize($maxPages > 0 ? $maxPages : null, (bool) $this->option('all'));

        $this->comment(sprintf(
            '%d RFCs in the index, %d pages downloaded, %d failed, %d left for the next run (%.2f seconds)',
            $result['rfcs'],
            $result['fetched'],
            $result['failed'],
            $result['remaining'],
            microtime(true) - $start,
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
