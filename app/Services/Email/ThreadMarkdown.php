<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Exceptions\NotFoundException;
use App\Models\Email;
use App\Support\Markdown\AgentLinks;
use Illuminate\Support\Collection;
use ZBateson\MailMimeParser\MailMimeParser;

/**
 * Renders a whole thread as a single Markdown document, for LLMs and agents.
 *
 * Messages are listed flat in chronological order (each one says which message
 * it replies to), which reads better than a nested tree in plain text.
 *
 * Long threads are paginated: agents' fetch tools truncate long documents.
 */
class ThreadMarkdown
{
    public const int PER_PAGE = 25;

    public function __construct(
        private readonly EmailContentParser $contentParser,
    ) {}

    /**
     * @param  Collection<int, Email>  $emails
     */
    public static function pageCount(Collection $emails): int
    {
        return max(1, (int) ceil($emails->count() / self::PER_PAGE));
    }

    /**
     * The page of the thread that contains the given email.
     *
     * @param  Collection<int, Email>  $emails
     */
    public static function pageOf(Collection $emails, int $number): int
    {
        $position = $emails->search(fn(Email $email) => $email->number === $number);

        return $position === false ? 1 : intdiv($position, self::PER_PAGE) + 1;
    }

    /**
     * All the emails of the thread, in chronological order.
     *
     * @return Collection<int, Email>
     */
    public function emails(Email $root): Collection
    {
        return Email::where('threadId', $root->threadId)->orderBy('date')->orderBy('number')->get();
    }

    /**
     * @throws NotFoundException  When the page doesn't exist.
     */
    public function render(Email $root, int $page = 1): string
    {
        $emails = $this->emails($root);
        $numbersById = $emails->pluck('number', 'id');
        $rootNumber = $root->number;
        $pageCount = self::pageCount($emails);
        if ($page < 1 || $page > $pageCount) {
            throw new NotFoundException("Page $page of thread $rootNumber does not exist");
        }
        $pageEmails = $emails->forPage($page, self::PER_PAGE);
        $nextPageUrl = $page < $pageCount ? url("/message/$rootNumber.md?page=" . ($page + 1)) : null;

        $lines = [
            '# ' . $root->subject,
            '',
            '- Thread: ' . url("/message/$rootNumber"),
            '- Original archive: https://news-web.php.net/php.internals/' . $rootNumber,
            '- Started by ' . $root->from->getNameOrEmail() . ' on ' . $root->date->format(DATE_ATOM),
            '- Messages: ' . $emails->count(),
        ];
        if ($pageCount > 1) {
            $lines[] = sprintf(
                '- Page %d of %d: messages %d to %d (%d per page, add `?page=N` to the URL)',
                $page,
                $pageCount,
                ($page - 1) * self::PER_PAGE + 1,
                ($page - 1) * self::PER_PAGE + $pageEmails->count(),
                self::PER_PAGE,
            );
        }
        if ($nextPageUrl) {
            $lines[] = '- Next page: ' . $nextPageUrl;
        }
        $lines[] = '- Reply by email to internals@lists.php.net';
        $lines[] = '- ' . AgentLinks::markdown();

        foreach ($pageEmails as $email) {
            $lines[] = '';
            $lines[] = '---';
            $lines[] = '';
            $lines[] = $this->renderEmail($email, $rootNumber, $numbersById);
        }

        if ($nextPageUrl) {
            $lines[] = '';
            $lines[] = '---';
            $lines[] = '';
            $lines[] = 'Next page: ' . $nextPageUrl;
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Returns the cleaned up text of an email as Markdown.
     *
     * The stored `content` is already rendered to HTML, so we go back to the
     * original text part of the email source.
     */
    public function content(Email $email): string
    {
        $text = $email->source !== ''
            ? (string) (new MailMimeParser)->parse($email->source, false)->getTextContent()
            : html_entity_decode(strip_tags($email->content), ENT_QUOTES | ENT_HTML5);

        return $this->collapseNestedQuotes(mb_trim($this->contentParser->toMarkdown($text)));
    }

    /**
     * @param  Collection<string, int>  $numbersById
     */
    private function renderEmail(Email $email, int $rootNumber, Collection $numbersById): string
    {
        $lines = [
            sprintf('## #%d — %s — %s', $email->number, $email->from->getNameOrEmail(), $email->date->format(DATE_ATOM)),
            '',
        ];

        $meta = ['URL: ' . url("/message/$rootNumber#{$email->number}")];
        $parentNumber = $email->inReplyTo !== null ? $numbersById->get($email->inReplyTo) : null;
        if ($parentNumber !== null) {
            $meta[] = "In reply to: #$parentNumber";
        }
        $lines[] = implode(' · ', $meta);
        $lines[] = '';
        $lines[] = $this->content($email);

        return implode("\n", $lines);
    }

    /**
     * Quotes of quotes repeat messages that are already in the thread:
     * replace each run of them with a single marker to save tokens.
     * First-level quotes are kept because inline replies rely on them.
     */
    private function collapseNestedQuotes(string $content): string
    {
        $output = [];
        $inNestedQuote = false;
        foreach (preg_split('/\R/', $content) ?: [] as $line) {
            if (preg_match('/^\s*>\s*>/', $line)) {
                if (! $inNestedQuote) {
                    $output[] = '> > […]';
                }
                $inNestedQuote = true;

                continue;
            }
            $inNestedQuote = false;
            $output[] = $line;
        }

        return implode("\n", $output);
    }
}
