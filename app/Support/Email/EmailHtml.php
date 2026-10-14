<?php

declare(strict_types=1);

namespace App\Support\Email;

use App\Support\CodeHighlighter;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Presentation of the stored HTML of an email, before it is displayed in a thread.
 */
class EmailHtml
{
    /** Lines at least this long were most likely wrapped by the mail client, not by the author. */
    private const int WRAPPED_LINE_LENGTH = 40;

    /** Quotes longer than this many lines are folded. */
    private const int FOLD_QUOTES_ABOVE_LINES = 3;

    public static function present(string $html): string
    {
        return app(CodeHighlighter::class)->highlight(self::foldLongQuotes(self::unwrapLines($html)));
    }

    /**
     * Emails are hard-wrapped around 72 characters, and every line break of the plain text is stored as `<br>`.
     * In a proportional font these breaks fall mid-sentence: join the lines that were wrapped,
     * keep the short ones (signatures, lists, code written without fences).
     */
    public static function unwrapLines(string $html): string
    {
        return (string) preg_replace_callback(
            '#(?<line>(?:^|(?<=>))[^\n]*?) <br>\n#m',
            function (array $matches): string {
                $text = html_entity_decode(strip_tags($matches['line']));

                return mb_strlen(mb_trim($text)) >= self::WRAPPED_LINE_LENGTH
                    ? $matches['line'] . ' '
                    : $matches[0];
            },
            $html,
        );
    }

    /**
     * Quotes of the previous messages are folded behind a "Quoted text" toggle, unless they are short.
     */
    public static function foldLongQuotes(string $html): string
    {
        if (! str_contains($html, '<blockquote')) {
            return $html;
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div id="email-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('email-root');
        if ($root === null) {
            return $html;
        }

        $quotes = (new DOMXPath($document))->query('./blockquote', $root);
        foreach ($quotes === false ? [] : $quotes as $quote) {
            if (! $quote instanceof DOMElement) {
                continue;
            }
            $lines = self::lineCount($quote);
            if ($lines <= self::FOLD_QUOTES_ABOVE_LINES) {
                continue;
            }
            $details = $document->createElement('details');
            $details->setAttribute('class', 'quote');
            $summary = $document->createElement('summary', "Quoted text · $lines lines");
            $details->appendChild($summary);
            $quote->parentNode?->replaceChild($details, $quote);
            $details->appendChild($quote);
        }

        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return $result;
    }

    private static function lineCount(DOMElement $quote): int
    {
        $html = (string) $quote->ownerDocument?->saveHTML($quote);
        $breaks = mb_substr_count($html, '<br>') + preg_match_all('#<(p|li|pre|h\d)[\s>]#', $html);

        return max(1, $breaks);
    }
}
