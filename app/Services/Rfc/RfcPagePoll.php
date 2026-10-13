<?php

declare(strict_types=1);

namespace App\Services\Rfc;

/**
 * A vote of an RFC page (DokuWiki "doodle" plugin).
 */
final readonly class RfcPagePoll
{
    /**
     * @param  list<array{label: string, count: int}>  $choices
     * @param  list<array{name: string, choices: list<string>, votedAt: ?string}>  $voters
     */
    public function __construct(
        public string $question,
        public bool $isClosed,
        public ?string $closesAt,
        public array $choices,
        public array $voters,
    ) {}

    /**
     * One line summary, e.g. "Yes: 33 · No: 7".
     */
    public function summary(): string
    {
        return implode(' · ', array_map(fn(array $choice) => "{$choice['label']}: {$choice['count']}", $this->choices));
    }
}
