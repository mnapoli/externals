<?php

declare(strict_types=1);

namespace App\Services\Search;

use Carbon\CarbonImmutable;

class SearchHit
{
    /**
     * Wraps the matched words in the snippet.
     * Private Use Area characters: they can't appear in emails.
     */
    public const string HIGHLIGHT_START = "\u{E000}";

    public const string HIGHLIGHT_END = "\u{E001}";

    public function __construct(
        public readonly int $number,
        public readonly string $subject,
        public readonly string $author,
        public readonly CarbonImmutable $date,
        /** Plain text, matched words wrapped in HIGHLIGHT_START/HIGHLIGHT_END */
        public readonly string $snippet,
    ) {}

    public function snippetAsHtml(): string
    {
        return str_replace([self::HIGHLIGHT_START, self::HIGHLIGHT_END], ['<mark>', '</mark>'], e($this->snippet));
    }

    public function snippetAsMarkdown(): string
    {
        return str_replace([self::HIGHLIGHT_START, self::HIGHLIGHT_END], '**', $this->snippet);
    }

    public function snippetAsText(): string
    {
        return str_replace([self::HIGHLIGHT_START, self::HIGHLIGHT_END], '', $this->snippet);
    }
}
