<?php

declare(strict_types=1);

namespace App\Services\Rfc;

use App\Models\Rfc;
use App\Models\RfcPoll;
use App\Support\Markdown\AgentLinks;

/**
 * Renders RFCs as Markdown, for LLMs and agents.
 */
class RfcMarkdown
{
    public function __construct(
        private readonly RfcList $list,
        private readonly RfcThreads $threads,
        private readonly RfcContent $content,
    ) {}

    public function renderList(): string
    {
        $lines = [
            '# PHP RFCs',
            '',
            '- The RFCs of https://wiki.php.net/rfc, synchronized every hour, by status (sections of the wiki index).',
            '- Each RFC links to its Markdown version: metadata, votes, the threads that discuss it, and its text.',
            '- JSON: ' . url('/api/rfcs') . ' (filter with `?status=voting`, `discussion`, `draft`, `accepted`, `implemented`, `declined`, `withdrawn`, `inactive` or `obsolete`)',
            '- ' . AgentLinks::markdown(),
        ];

        foreach ($this->list->sections() as $section) {
            $lines[] = '';
            $lines[] = '## ' . $section['title'];
            $lines[] = '';
            foreach ($section['rfcs'] as $rfc) {
                $line = sprintf('- [%s](%s)', $this->escape($rfc->title), $rfc->url() . '.md');
                $poll = $rfc->status === RfcStatus::Voting ? $rfc->currentPoll() : null;
                if ($poll instanceof RfcPoll) {
                    $line .= ' — vote: ' . $poll->summary() . ' (' . $poll->stateLabel() . ')';
                }
                $lines[] = $line;
            }
        }

        return implode("\n", $lines) . "\n";
    }

    public function render(Rfc $rfc): string
    {
        $lines = [
            '# RFC: ' . $rfc->title,
            '',
            '- Status: ' . $rfc->statusLabel() . ($rfc->isListed ? '' : ' (no longer listed on the wiki index)'),
        ];
        foreach (['Authors' => $rfc->authors, 'Date' => $rfc->date, 'Version' => $rfc->version, 'Target' => $rfc->targetVersion, 'Implementation' => $rfc->implementation] as $label => $value) {
            if ($value !== null) {
                $lines[] = "- $label: $value";
            }
        }
        $lines[] = '- Original: ' . $rfc->wikiUrl();
        $lines[] = '- Page: ' . $rfc->url();
        $lines[] = '- ' . AgentLinks::markdown();

        if ($rfc->polls->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '## Votes';
            foreach ($rfc->polls as $poll) {
                $lines[] = '';
                $lines[] = sprintf('### %s (%s)', $poll->question, $poll->stateLabel());
                $lines[] = '';
                $lines[] = $poll->summary();
                if ($poll->voters !== []) {
                    $lines[] = '';
                    $lines[] = 'Voters: ' . implode(', ', array_map(
                        fn(array $voter) => $voter['name'] . ' (' . implode(', ', $voter['choices']) . ')',
                        $poll->voters,
                    ));
                }
            }
        }

        $lines[] = '';
        $lines[] = '## Discussions on the mailing list';
        $lines[] = '';
        $threads = $this->threads->threadsOf($rfc->slug);
        if ($threads->isEmpty()) {
            $lines[] = 'No thread found.';
        }
        foreach ($threads as $thread) {
            $lines[] = sprintf(
                '- [%s](%s) — started on %s, %d messages',
                $this->escape($thread->subject),
                url("/message/{$thread->number}.md"),
                $thread->date->format('Y-m-d'),
                $thread->emailCount,
            );
        }

        // The text has its own "##" sections
        $lines[] = '';
        $lines[] = '---';
        $lines[] = '';
        $lines[] = $rfc->content !== null
            ? $this->content->markdown($rfc)
            : 'Not imported yet, read it on ' . $rfc->wikiUrl();

        return implode("\n", $lines) . "\n";
    }

    private function escape(string $text): string
    {
        return str_replace(['[', ']'], ['\[', '\]'], $text);
    }
}
