<?php

declare(strict_types=1);

namespace App\Services\Rfc;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\XPath;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * Parses the HTML of an RFC page, as exported by DokuWiki with `?do=export_xhtmlbody`.
 *
 * The rendered HTML is used rather than the DokuWiki source because it contains
 * the results of the votes, and because converting HTML to Markdown is a solved problem.
 */
class RfcPageParser
{
    public const string WIKI_URL = 'https://wiki.php.net';

    /** Keys of the header list of RFC pages ("Version: 1.0"…), lowercased, mapped to our fields. */
    private const array METADATA_KEYS = [
        'version' => 'version',
        'date' => 'date',
        'created' => 'date',
        'author' => 'authors',
        'authors' => 'authors',
        'author(s)' => 'authors',
        'status' => 'rawStatus',
        'target' => 'targetVersion',
        'target version' => 'targetVersion',
        'targets' => 'targetVersion',
        'implementation' => 'implementation',
    ];

    public function parse(string $html): RfcPage
    {
        $document = HTMLDocument::createFromString(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>',
            LIBXML_NOERROR,
        );
        $body = $document->body;
        assert($body !== null);

        $title = null;
        $h1 = $body->querySelector('h1');
        if ($h1 !== null) {
            $title = $this->text($h1);
            $title = (string) preg_replace('/^PHP RFC\s*:\s*/i', '', $title);
            $h1->remove();
        }

        $metadata = $this->extractMetadata($body);
        $linkedEmailNumbers = $this->extractLinkedEmails($body);
        $polls = $this->extractPolls($document, $body);
        $this->cleanUp($document, $body);

        return new RfcPage(
            title: $title !== '' ? $title : null,
            metadata: $metadata,
            polls: $polls,
            content: $this->toMarkdown($body->innerHTML),
            linkedEmailNumbers: $linkedEmailNumbers,
        );
    }

    /**
     * RFC pages start with a list of "Key: value" items (version, author, status…).
     *
     * @return array{version: ?string, date: ?string, authors: ?string, rawStatus: ?string, targetVersion: ?string, implementation: ?string}
     */
    private function extractMetadata(Element $body): array
    {
        $metadata = [
            'version' => null,
            'date' => null,
            'authors' => null,
            'rawStatus' => null,
            'targetVersion' => null,
            'implementation' => null,
        ];

        // The header list is the first list of the page, before any subsection
        $list = $body->querySelector('ul');
        $firstSection = $body->querySelector('h2');
        if ($list === null || ($firstSection !== null && ($firstSection->compareDocumentPosition($list) & 4) !== 0)) {
            return $metadata;
        }

        $isHeader = false;
        foreach ($list->children as $item) {
            if ($item->localName !== 'li' || ! preg_match('/^([^:]{1,30}):\s*(.*)$/s', $this->text($item), $matches)) {
                continue;
            }
            $field = self::METADATA_KEYS[mb_strtolower(mb_trim($matches[1]))] ?? null;
            if ($field !== null && $metadata[$field] === null && mb_trim($matches[2]) !== '') {
                $metadata[$field] = mb_substr(mb_trim($matches[2]), 0, 255);
                $isHeader = true;
            }
        }

        if ($isHeader) {
            // Shown separately, no need to keep it in the content
            $list->remove();
        }

        return $metadata;
    }

    /**
     * @return list<int>
     */
    private function extractLinkedEmails(Element $body): array
    {
        $numbers = [];
        foreach ($body->querySelectorAll('a[href]') as $link) {
            $href = (string) $link->getAttribute('href');
            if (preg_match('#^https?://(?:externals\.io/message/|news-web\.php\.net/php\.internals/|news\.php\.net/php\.internals/)(\d+)#i', $href, $matches)) {
                $numbers[(int) $matches[1]] = true;
            }
        }

        return array_keys($numbers);
    }

    /**
     * Votes are rendered as tables: one row per voter, one column per choice.
     *
     * Each vote is replaced in the content by a one line summary: the details
     * are stored separately.
     *
     * @return list<RfcPagePoll>
     */
    private function extractPolls(HTMLDocument $document, Element $body): array
    {
        $polls = [];
        foreach ($body->querySelectorAll('table.doodle__results') as $table) {
            $question = '';
            $status = '';
            foreach ($table->querySelectorAll('tr.title_row') as $index => $row) {
                if ($index === 0) {
                    $question = $this->text($row);
                } else {
                    $status = $this->text($row);
                }
            }

            $labels = array_map(
                fn(Element $cell) => $this->text($cell),
                iterator_to_array($table->querySelectorAll('tr.fields_row .fields_data')),
            );

            $voters = [];
            $counts = array_fill(0, count($labels), 0);
            foreach ($table->querySelectorAll('tr.data_row') as $row) {
                $name = $this->text($row->querySelector('.data_caption') ?? $row);
                $choices = [];
                $votedAt = null;
                foreach ($row->querySelectorAll('.data_data') as $index => $cell) {
                    $image = $cell->querySelector('img');
                    if ($image === null || ! isset($labels[$index])) {
                        continue;
                    }
                    $choices[] = $labels[$index];
                    $counts[$index]++;
                    // e.g. "2025/05/14 06:37"
                    $date = str_replace('/', '-', (string) $image->getAttribute('title'));
                    $votedAt ??= preg_match('/^\d{4}-\d{2}-\d{2}/', $date) ? $date : null;
                }
                if ($name !== '' && $choices !== []) {
                    $voters[] = ['name' => $name, 'choices' => $choices, 'votedAt' => $votedAt];
                }
            }

            // The totals of the wiki take precedence: they include votes of deleted accounts
            $results = array_values(array_map(
                fn(Element $cell) => (int) $this->text($cell),
                iterator_to_array($table->querySelectorAll('tr.results_row .results_data')),
            ));
            if (count($results) === count($labels)) {
                $counts = $results;
            }

            $closesAt = preg_match('/will close on (.+?)\.?$/i', $status, $matches) ? $matches[1] : null;

            $poll = new RfcPagePoll(
                question: $question,
                isClosed: str_contains(mb_strtolower($status), 'has been closed'),
                closesAt: $closesAt,
                choices: array_map(
                    fn(string $label, int $count) => ['label' => $label, 'count' => $count],
                    $labels,
                    $counts,
                ),
                voters: $voters,
            );
            $polls[] = $poll;

            // Replace the voting form (or the table when there is no form) by a summary
            $container = $table->closest('form') ?? $table;
            $summary = $document->createElement('p');
            $strong = $document->createElement('strong');
            $strong->textContent = 'Vote: ' . $question;
            $summary->append($strong, ' — ' . $poll->summary() . ($poll->isClosed ? ' (closed)' : ' (open)'));
            $container->replaceWith($summary);
        }

        return $polls;
    }

    private function cleanUp(HTMLDocument $document, Element $body): void
    {
        foreach ((new XPath($document))->query('//comment()') as $comment) {
            $comment->parentNode?->removeChild($comment);
        }
        foreach ($body->querySelectorAll('script, style, form, input, button, #dw__toc, .secedit, .doodle__form') as $element) {
            $element->remove();
        }
        // Wrappers that would add blank lines or noise to the Markdown
        foreach ($body->querySelectorAll('abbr, div.li') as $element) {
            $element->replaceWith(...iterator_to_array($element->childNodes));
        }

        // Highlighted code: keep the text only, and the language for the Markdown code fence
        foreach ($body->querySelectorAll('pre') as $pre) {
            $language = preg_match('/\bcode\s+([a-z0-9_+-]+)/i', (string) $pre->getAttribute('class'), $matches) ? $matches[1] : null;
            $code = $document->createElement('code');
            $code->textContent = $pre->textContent ?? '';
            if ($language !== null) {
                $code->setAttribute('class', 'language-' . $language);
            }
            $newPre = $document->createElement('pre');
            $newPre->append($code);
            $pre->replaceWith($newPre);
        }

        // Inline highlighted code
        foreach ($body->querySelectorAll('code') as $code) {
            if ($code->querySelector('*') !== null) {
                $code->textContent = $code->textContent;
            }
        }

        foreach ($body->querySelectorAll('a[href]') as $link) {
            $link->setAttribute('href', $this->absoluteUrl((string) $link->getAttribute('href')));
            $link->removeAttribute('title');
        }
        foreach ($body->querySelectorAll('img[src]') as $image) {
            $image->setAttribute('src', $this->absoluteUrl((string) $image->getAttribute('src')));
        }
    }

    private function toMarkdown(string $html): string
    {
        $converter = new HtmlConverter([
            'header_style' => 'atx',
            'strip_tags' => true,
            'remove_nodes' => 'script style',
            'hard_break' => true,
        ]);
        $converter->getEnvironment()->addConverter(new TableConverter);

        $markdown = $converter->convert($html);

        // Collapse the blank lines left by removed elements
        return mb_trim((string) preg_replace("/\n{3,}/", "\n\n", $markdown));
    }

    private function absoluteUrl(string $url): string
    {
        return str_starts_with($url, '/') && ! str_starts_with($url, '//') ? self::WIKI_URL . $url : $url;
    }

    private function text(Element $element): string
    {
        return mb_trim((string) preg_replace('/\s+/u', ' ', $element->textContent ?? ''));
    }
}
