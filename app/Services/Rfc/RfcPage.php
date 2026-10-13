<?php

declare(strict_types=1);

namespace App\Services\Rfc;

/**
 * The content of an RFC page of wiki.php.net, parsed.
 */
final readonly class RfcPage
{
    /**
     * @param  array{version: ?string, date: ?string, authors: ?string, rawStatus: ?string, targetVersion: ?string, implementation: ?string}  $metadata
     * @param  list<RfcPagePoll>  $polls
     * @param  list<int>  $linkedEmailNumbers  Emails of php.internals the page links to
     */
    public function __construct(
        public ?string $title,
        public array $metadata,
        public array $polls,
        public string $content,
        public array $linkedEmailNumbers,
    ) {}
}
