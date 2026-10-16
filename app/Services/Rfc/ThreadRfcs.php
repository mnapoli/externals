<?php

declare(strict_types=1);

namespace App\Services\Rfc;

use App\Models\Rfc;

/**
 * The RFCs linked to a thread: the RFC the thread is about, if any, and the RFCs it only mentions.
 *
 * The root email of an RFC thread often links to older RFCs (prior art, alternatives):
 * those must not be shown as if the thread was about them.
 */
final readonly class ThreadRfcs
{
    /** Share of the words of an RFC title that must appear in the subject of the thread. */
    private const float TITLE_MATCH = 0.5;

    private const array IGNORED_WORDS = ['the', 'and', 'for', 'with', 'rfc', 'discussion', 'vote', 'voting', 'pre', 'idea', 'php'];

    /**
     * @param  list<Rfc>  $mentions
     */
    public function __construct(
        public ?Rfc $primary,
        public array $mentions,
    ) {}

    /**
     * @param  list<array{rfc: Rfc, fromWiki: bool}>  $links  One per RFC
     */
    public static function classify(string $subject, array $links): self
    {
        $primary = self::findPrimary($subject, $links);
        $mentions = [];
        foreach ($links as ['rfc' => $rfc]) {
            if ($rfc !== $primary) {
                $mentions[] = $rfc;
            }
        }

        return new self($primary, $mentions);
    }

    public static function empty(): self
    {
        return new self(null, []);
    }

    /**
     * @param  list<array{rfc: Rfc, fromWiki: bool}>  $links
     */
    private static function findPrimary(string $subject, array $links): ?Rfc
    {
        // The RFC page links to the thread: its author says it's the discussion of the RFC
        $fromWiki = array_values(array_filter($links, fn(array $link) => $link['fromWiki']));
        if (count($fromWiki) === 1) {
            return $fromWiki[0]['rfc'];
        }

        if (count($links) === 1) {
            return self::looselyMatches($subject, $links[0]['rfc']) ? $links[0]['rfc'] : null;
        }

        return self::bestTitleMatch($subject, $fromWiki !== [] ? $fromWiki : $links);
    }

    /**
     * Whether the subject shares half of the words of the shorter of it and the title (or the slug) of the RFC.
     * Without other RFCs to compare with, a subject phrased differently ("BLAKE3 hash redux", "RFC: Enum" for
     * "Enumerated Types", slug "enum") still counts, but not a thread that only cites a process RFC
     * ("Voting access" for "Voting Process").
     */
    private static function looselyMatches(string $subject, Rfc $rfc): bool
    {
        $subjectWords = self::words($subject);
        if ($subjectWords === []) {
            // e.g. "[RFC] [VOTE]": nothing says the thread is about something else
            return true;
        }
        foreach ([$rfc->title, $rfc->slug] as $name) {
            $nameWords = self::words($name);
            if ($nameWords !== [] && count(array_intersect($nameWords, $subjectWords)) / min(count($nameWords), count($subjectWords)) >= self::TITLE_MATCH) {
                return true;
            }
        }

        return false;
    }

    /**
     * The RFC whose title clearly matches the subject, if only one does.
     *
     * @param  list<array{rfc: Rfc, fromWiki: bool}>  $links
     */
    private static function bestTitleMatch(string $subject, array $links): ?Rfc
    {
        $subjectWords = self::words($subject);
        $scores = [];
        foreach ($links as $index => ['rfc' => $rfc]) {
            $titleWords = self::words($rfc->title);
            $scores[$index] = $titleWords === [] ? 0.0 : count(array_intersect($titleWords, $subjectWords)) / count($titleWords);
        }
        arsort($scores);
        $best = array_key_first($scores);
        if ($best === null || $scores[$best] < self::TITLE_MATCH) {
            return null;
        }
        $ranked = array_values($scores);
        if (isset($ranked[1]) && $ranked[1] === $ranked[0]) {
            return null;
        }

        return $links[$best]['rfc'];
    }

    /**
     * @return list<string>
     */
    private static function words(string $text): array
    {
        $text = mb_strtolower((string) preg_replace('/\[[^\]]*\]/', ' ', $text));
        $words = preg_split('/[^\p{L}\p{N}]+/u', $text) ?: [];

        $words = array_filter($words, fn(string $word) => mb_strlen($word) > 2 && ! in_array($word, self::IGNORED_WORDS, true));

        // "checksums" matches "checksum", "class" stays "class"
        return array_values(array_unique(array_map(
            fn(string $word) => mb_strlen($word) > 3 && preg_match('/[^s]s$/', $word) ? mb_substr($word, 0, -1) : $word,
            $words,
        )));
    }

    /**
     * Without the RFCs no longer listed on the wiki index. A thread about one of them only mentions the others.
     */
    public function listedOnly(): self
    {
        return new self(
            $this->primary?->isListed ? $this->primary : null,
            array_values(array_filter($this->mentions, fn(Rfc $rfc) => $rfc->isListed)),
        );
    }
}
