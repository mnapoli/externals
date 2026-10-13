<?php

declare(strict_types=1);

namespace App\Services\Rfc;

use Carbon\CarbonImmutable;

/**
 * Activity on the mailing list about an RFC.
 */
final readonly class RfcActivity
{
    public function __construct(
        public int $threadCount,
        public ?CarbonImmutable $lastActivity,
    ) {}
}
