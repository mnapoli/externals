<?php

declare(strict_types=1);

namespace App\Services\Rfc;

use Carbon\CarbonImmutable;

/**
 * A thread that discusses an RFC.
 */
final readonly class LinkedThread
{
    public function __construct(
        public int $number,
        public string $subject,
        public CarbonImmutable $date,
        public ?string $fromName,
        public int $emailCount,
    ) {}
}
