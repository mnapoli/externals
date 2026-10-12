<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Models\Email;
use App\Support\Markdown\AgentLinks;
use Illuminate\Support\Collection;
use ZBateson\MailMimeParser\MailMimeParser;

/**
 * Renders a whole thread as a single Markdown document, for LLMs and agents.
 *
 * Messages are listed flat in chronological order (each one says which message
 * it replies to), which reads better than a nested tree in plain text.
 */
class ThreadMarkdown
{
    public function __construct(
        private readonly EmailContentParser $contentParser,
    ) {}

    public function render(Email $root): string
    {
        $emails = Email::where('threadId', $root->threadId)->orderBy('date')->get();
        $numbersById = $emails->pluck('number', 'id');
        $rootNumber = $root->number;

        $lines = [
            '# ' . $root->subject,
            '',
            '- Thread: ' . url("/message/$rootNumber"),
            '- Original archive: https://news-web.php.net/php.internals/' . $rootNumber,
            '- Started by ' . $root->from->getNameOrEmail() . ' on ' . $root->date->format(DATE_ATOM),
            '- Messages: ' . $emails->count(),
            '- Reply by email to internals@lists.php.net',
            '- ' . AgentLinks::markdown(),
        ];

        foreach ($emails as $email) {
            $lines[] = '';
            $lines[] = '---';
            $lines[] = '';
            $lines[] = $this->renderEmail($email, $rootNumber, $numbersById);
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
