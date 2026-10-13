<?php

declare(strict_types=1);

namespace App\Services\Rfc;

/**
 * An RFC as listed on https://wiki.php.net/rfc.
 */
final readonly class RfcIndexEntry
{
    public function __construct(
        public string $slug,
        public string $title,
        public RfcStatus $status,
        public ?string $subsection,
        public ?string $phpVersion,
        public int $position,
    ) {}
}
