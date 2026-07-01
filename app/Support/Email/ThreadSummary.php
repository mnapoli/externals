<?php

declare(strict_types=1);

namespace App\Support\Email;

use Carbon\CarbonImmutable;
use stdClass;

/**
 * A single row of a thread listing (home, top, RFC feed).
 *
 * Replaces the associative arrays that the multi-join aggregation queries
 * in ThreadQuery used to leak into views and the RSS builder.
 */
class ThreadSummary
{
    public function __construct(
        public readonly int $number,
        public readonly string $subject,
        public readonly CarbonImmutable $date,
        public readonly ?string $fromName,
        public readonly ?string $fromEmail,
        public readonly int $emailCount,
        public readonly CarbonImmutable $lastUpdate,
        public readonly int $votes,
        public readonly bool $isRead,
        public readonly ?int $userVote,
    ) {}

    public static function fromRow(stdClass $row): self
    {
        return new self(
            number: (int) $row->number,
            subject: (string) $row->subject,
            date: CarbonImmutable::parse($row->date),
            fromName: $row->fromName !== null ? (string) $row->fromName : null,
            fromEmail: isset($row->fromEmail) ? (string) $row->fromEmail : null,
            emailCount: (int) $row->emailCount,
            lastUpdate: CarbonImmutable::parse($row->lastUpdate),
            votes: (int) $row->votes,
            isRead: (bool) $row->isRead,
            userVote: $row->userVote !== null ? (int) $row->userVote : null,
        );
    }
}
