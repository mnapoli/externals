<?php

declare(strict_types=1);

namespace App\Services\Rfc;

use App\Models\Rfc;
use App\Models\RfcPoll;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Throwable;

/**
 * Imports RFCs from https://wiki.php.net/rfc.
 *
 * The index page is read on every run (statuses change there). RFC pages are
 * only downloaded when needed: never fetched yet, changed on the wiki, or
 * being voted on (votes don't show up as page changes).
 */
class RfcSynchronizer
{
    /** Pause between two page downloads, to be gentle with the wiki. */
    private const int DELAY_MICROSECONDS = 200_000;

    public function __construct(
        private readonly WikiClient $wiki,
        private readonly RfcIndexParser $indexParser,
        private readonly RfcPageParser $pageParser,
        private readonly RfcThreads $threads,
    ) {}

    /**
     * @param  ?int  $maxPages  Maximum number of RFC pages to download, null for no limit
     * @param  bool  $all  Download all pages, e.g. after changing how they are parsed
     * @return array{rfcs: int, fetched: int, failed: int, remaining: int}
     */
    public function synchronize(?int $maxPages = null, bool $all = false): array
    {
        $entries = $this->indexParser->parse($this->wiki->index());
        $previousStatuses = $this->updateIndex($entries);

        try {
            $changes = $this->wiki->recentChanges();
        } catch (Throwable $e) {
            Log::warning('Cannot read the recent changes of the wiki: ' . $e->getMessage());
            $changes = [];
        }

        $toFetch = Rfc::where('isListed', true)
            ->orderBy('indexPosition')
            ->get()
            ->filter(fn(Rfc $rfc) => $all
                || $rfc->fetchedAt === null
                || $rfc->status === RfcStatus::Voting
                // Voting has ended: get the final results
                || ($previousStatuses[$rfc->slug] ?? $rfc->status) !== $rfc->status
                || (isset($changes[$rfc->slug]) && $changes[$rfc->slug] > $rfc->fetchedAt))
            // Already imported RFCs first: their updates matter more than the backlog
            ->sortBy(fn(Rfc $rfc) => $rfc->fetchedAt === null ? 1 : 0)
            ->values();

        $fetched = 0;
        $failed = 0;
        foreach ($toFetch as $rfc) {
            if ($maxPages !== null && $fetched + $failed >= $maxPages) {
                break;
            }
            if ($fetched + $failed > 0) {
                Sleep::usleep(self::DELAY_MICROSECONDS);
            }
            try {
                $this->synchronizePage($rfc);
                $fetched++;
            } catch (Throwable $e) {
                Log::warning("Cannot synchronize RFC {$rfc->slug}: " . $e->getMessage());
                $failed++;
            }
        }

        return [
            'rfcs' => count($entries),
            'fetched' => $fetched,
            'failed' => $failed,
            'remaining' => $toFetch->count() - $fetched - $failed,
        ];
    }

    public function synchronizePage(Rfc $rfc): void
    {
        $page = $this->pageParser->parse($this->wiki->page($rfc->slug));

        DB::transaction(function () use ($rfc, $page): void {
            $rfc->fill($page->metadata);
            $rfc->content = $page->content;
            $rfc->fetchedAt = now()->toImmutable();
            $rfc->save();

            $rfc->polls()->delete();
            foreach ($page->polls as $position => $poll) {
                RfcPoll::create([
                    'rfcId' => $rfc->id,
                    'position' => $position,
                    'question' => $poll->question,
                    'isClosed' => $poll->isClosed,
                    'closesAt' => $poll->closesAt,
                    'choices' => $poll->choices,
                    'voters' => $poll->voters,
                ]);
            }

            $this->threads->replaceWikiLinks($rfc->slug, $page->linkedEmailNumbers);
        });
    }

    /**
     * @param  list<RfcIndexEntry>  $entries
     * @return array<string, RfcStatus> Statuses before the update, by slug
     */
    private function updateIndex(array $entries): array
    {
        $existing = Rfc::all()->keyBy('slug');
        $previousStatuses = $existing->map(fn(Rfc $rfc) => $rfc->status)->all();

        DB::transaction(function () use ($entries, $existing): void {
            foreach ($entries as $entry) {
                $rfc = $existing->get($entry->slug) ?? new Rfc(['slug' => $entry->slug]);
                $rfc->fill([
                    'title' => mb_substr($entry->title, 0, 255),
                    'status' => $entry->status,
                    'indexSubsection' => $entry->subsection !== null ? mb_substr($entry->subsection, 0, 255) : null,
                    'phpVersion' => $entry->phpVersion,
                    'indexPosition' => $entry->position,
                    'isListed' => true,
                ]);
                if ($rfc->isDirty()) {
                    $rfc->save();
                }
            }

            $listedSlugs = array_map(fn(RfcIndexEntry $entry) => $entry->slug, $entries);
            Rfc::whereNotIn('slug', $listedSlugs)->where('isListed', true)->update(['isListed' => false]);
        });

        return $previousStatuses;
    }
}
