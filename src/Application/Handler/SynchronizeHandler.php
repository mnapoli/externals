<?php declare(strict_types=1);

namespace Externals\Application\Handler;

use Bref\Context\Context;
use Bref\Event\Handler;
use Externals\EmailSynchronizer;

class SynchronizeHandler implements Handler
{
    public function __construct(
        private EmailSynchronizer $synchronizer,
    ) {
    }

    public function handle($event, Context $context): void
    {
        $this->synchronizer->synchronize();
    }
}
