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
        if (count($links) === 1) {
            return $links[0]['rfc'];
        }

        // The RFC page links to the thread: its author says it's the discussion of the RFC
        $fromWiki = array_values(array_filter($links, fn(array $link) => $link['fromWiki']));
        if (count($fromWiki) === 1) {
            return $fromWiki[0]['rfc'];
        }

        return self::bestTitleMatch($subject, $fromWiki !== [] ? $fromWiki : $links);
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

        return array_values(array_unique(array_filter(
            $words,
            fn(string $word) => mb_strlen($word) > 2 && ! in_array($word, self::IGNORED_WORDS, true),
        )));
    }
}
