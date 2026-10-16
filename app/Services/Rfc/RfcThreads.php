<?php

declare(strict_types=1);

namespace App\Services\Rfc;

use App\Models\Email;
use App\Models\Rfc;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Links between RFCs and the threads that discuss them (table `rfc_threads`).
 *
 * - "email" links: the root email of the thread links to the RFC page.
 * - "wiki" links: the RFC page links to the thread.
 */
class RfcThreads
{
    public const string SOURCE_EMAIL = 'email';
    public const string SOURCE_WIKI = 'wiki';

    public function __construct(
        private readonly RfcLinkExtractor $extractor,
    ) {}

    /**
     * Links a new thread to the RFCs its root email links to.
     *
     * @return int The number of links created
     */
    public function linkThreadRoot(Email $email): int
    {
        if (! $email->isThreadRoot()) {
            return 0;
        }

        $rows = array_map(fn(string $slug) => [
            'rfcSlug' => $slug,
            'threadNumber' => $email->number,
            'source' => self::SOURCE_EMAIL,
        ], $this->extractor->extractFromThreadRoot($email->content));

        return DB::table('rfc_threads')->insertOrIgnore($rows);
    }

    /**
     * Scans the root emails of all threads, for the initial import.
     *
     * @return int The number of links created
     */
    public function linkAllThreadRoots(): int
    {
        $count = 0;
        Email::query()
            ->select(['id', 'number', 'threadId', 'content'])
            ->where('isThreadRoot', true)
            ->where('content', 'like', '%wiki.php.net%')
            ->chunkById(500, function (EloquentCollection $emails) use (&$count): void {
                foreach ($emails as $email) {
                    /** @var Email $email */
                    $count += $this->linkThreadRoot($email);
                }
            }, 'number');

        return $count;
    }

    /**
     * Replaces the threads an RFC page links to.
     *
     * @param  list<int>  $emailNumbers  Any email of the threads, not necessarily the root
     */
    public function replaceWikiLinks(string $slug, array $emailNumbers): void
    {
        $threadNumbers = $this->threadNumbersOf($emailNumbers);

        DB::table('rfc_threads')->where('rfcSlug', $slug)->where('source', self::SOURCE_WIKI)->delete();
        DB::table('rfc_threads')->insertOrIgnore(array_map(fn(int $number) => [
            'rfcSlug' => $slug,
            'threadNumber' => $number,
            'source' => self::SOURCE_WIKI,
        ], $threadNumbers));
    }

    /**
     * The threads linked to an RFC, oldest first.
     *
     * @return Collection<int, LinkedThread>
     */
    public function threadsOf(string $slug): Collection
    {
        return DB::table('emails')
            ->join('threads', 'threads.emailId', '=', 'emails.id')
            ->whereIn('emails.number', DB::table('rfc_threads')->select('threadNumber')->where('rfcSlug', $slug))
            ->orderBy('emails.date')
            ->get(['emails.number', 'emails.subject', 'emails.date', 'emails.fromName', 'threads.emailCount'])
            ->map(fn(object $row) => new LinkedThread(
                number: (int) $row->number,
                subject: (string) $row->subject,
                date: CarbonImmutable::parse($row->date),
                fromName: $row->fromName !== null ? (string) $row->fromName : null,
                emailCount: (int) $row->emailCount,
            ));
    }

    /**
     * The (listed) RFCs a thread is linked to.
     *
     * @return EloquentCollection<int, Rfc>
     */
    public function rfcsOf(int $threadNumber): EloquentCollection
    {
        return Rfc::query()
            ->whereIn('slug', DB::table('rfc_threads')->select('rfcSlug')->where('threadNumber', $threadNumber))
            ->where('isListed', true)
            ->with('polls')
            ->orderBy('indexPosition')
            ->get();
    }

    /**
     * The RFC each thread is about, and the RFCs it only mentions.
     *
     * @param  array<int, string>  $subjects  Subjects of the threads, keyed by thread number
     * @param  bool  $listedOnly  Hide the RFCs that are no longer listed on the wiki index (they still count to find
     *                            the RFC of the thread, so that it is the same on every page)
     * @return array<int, ThreadRfcs> Keyed by thread number, only the threads linked to RFCs
     */
    public function rfcLinksOfThreads(array $subjects, bool $listedOnly = true): array
    {
        if ($subjects === []) {
            return [];
        }

        $rows = Rfc::query()
            ->join('rfc_threads', 'rfc_threads.rfcSlug', '=', 'rfcs.slug')
            ->whereIn('rfc_threads.threadNumber', array_keys($subjects))
            ->orderBy('rfcs.indexPosition')
            ->get(['rfcs.id', 'rfcs.slug', 'rfcs.title', 'rfcs.status', 'rfcs.phpVersion', 'rfcs.isListed', 'rfcs.indexPosition', 'rfc_threads.threadNumber', 'rfc_threads.source']);

        // A thread can be linked to an RFC twice: from its email and from the wiki
        $links = [];
        foreach ($rows as $rfc) {
            $threadNumber = (int) $rfc->getAttribute('threadNumber');
            $fromWiki = $rfc->getAttribute('source') === self::SOURCE_WIKI;
            if (isset($links[$threadNumber][$rfc->slug])) {
                $links[$threadNumber][$rfc->slug]['fromWiki'] = $links[$threadNumber][$rfc->slug]['fromWiki'] || $fromWiki;
            } else {
                $links[$threadNumber][$rfc->slug] = ['rfc' => $rfc, 'fromWiki' => $fromWiki];
            }
        }

        $threadRfcs = [];
        foreach ($links as $threadNumber => $threadLinks) {
            $classified = ThreadRfcs::classify($subjects[$threadNumber] ?? '', array_values($threadLinks));
            if ($listedOnly) {
                $classified = $classified->listedOnly();
                if ($classified->primary === null && $classified->mentions === []) {
                    continue;
                }
            }
            $threadRfcs[$threadNumber] = $classified;
        }

        return $threadRfcs;
    }

    /**
     * The threads about an RFC, and the threads that only mention it, oldest first.
     *
     * @return array{discussions: Collection<int, LinkedThread>, mentions: Collection<int, LinkedThread>}
     */
    public function discussionsOf(Rfc $rfc): array
    {
        $threads = $this->threadsOf($rfc->slug);
        $links = $this->rfcLinksOfThreads(
            $threads->mapWithKeys(fn(LinkedThread $thread) => [$thread->number => $thread->subject])->all(),
            listedOnly: false,
        );
        [$discussions, $mentions] = $threads->partition(
            fn(LinkedThread $thread) => ($links[$thread->number] ?? null)?->primary?->slug === $rfc->slug,
        );

        return ['discussions' => $discussions->values(), 'mentions' => $mentions->values()];
    }

    /**
     * Number of threads about each RFC and date of their latest activity.
     * A thread that only mentions an RFC is not activity on it: it would bring back old RFCs cited as prior art.
     *
     * @param  array<string>|null  $slugs  The RFCs to look at, null for all of them (slower: every link is classified)
     * @return Collection<string, RfcActivity> Keyed by RFC slug
     */
    public function activityByRfc(?array $slugs = null): Collection
    {
        if ($slugs === []) {
            return collect();
        }
        $threads = DB::table('emails')
            ->join('threads', 'threads.emailId', '=', 'emails.id')
            ->whereIn('emails.number', DB::table('rfc_threads')->select('threadNumber')->when($slugs !== null, fn($query) => $query->whereIn('rfcSlug', $slugs)))
            ->get(['emails.number', 'emails.subject', 'threads.lastUpdate']);
        $links = $this->rfcLinksOfThreads(
            $threads->mapWithKeys(fn(object $thread) => [(int) $thread->number => (string) $thread->subject])->all(),
            listedOnly: false,
        );

        $counts = [];
        $lastUpdates = [];
        foreach ($threads as $thread) {
            $slug = ($links[(int) $thread->number] ?? null)?->primary?->slug;
            if ($slug === null || ($slugs !== null && ! in_array($slug, $slugs, true))) {
                continue;
            }
            $counts[$slug] = ($counts[$slug] ?? 0) + 1;
            $lastUpdate = $thread->lastUpdate !== null ? CarbonImmutable::parse($thread->lastUpdate) : null;
            if ($lastUpdate !== null && ($lastUpdates[$slug] ?? null)?->greaterThan($lastUpdate) !== true) {
                $lastUpdates[$slug] = $lastUpdate;
            }
        }

        return collect($counts)->map(fn(int $count, int|string $slug) => new RfcActivity(
            threadCount: $count,
            lastActivity: $lastUpdates[$slug] ?? null,
        ));
    }

    /**
     * @param  list<int>  $emailNumbers
     * @return list<int>
     */
    private function threadNumbersOf(array $emailNumbers): array
    {
        if ($emailNumbers === []) {
            return [];
        }

        $emails = Email::whereIn('number', $emailNumbers)->get(['id', 'number', 'threadId']);
        $roots = Email::whereIn('id', $emails->pluck('threadId')->filter()->unique())->get(['id', 'number'])->keyBy('id');

        $numbers = [];
        foreach ($emails as $email) {
            $root = $email->threadId !== null ? $roots->get($email->threadId) : null;
            $numbers[] = $root->number ?? $email->number;
        }

        return array_values(array_unique($numbers));
    }
}
