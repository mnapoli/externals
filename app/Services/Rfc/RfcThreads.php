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
     * Number of linked threads and date of the latest activity, for all RFCs.
     *
     * @return Collection<string, RfcActivity> Keyed by RFC slug
     */
    public function activityByRfc(): Collection
    {
        return DB::table('rfc_threads')
            ->join('emails', 'emails.number', '=', 'rfc_threads.threadNumber')
            ->join('threads', 'threads.emailId', '=', 'emails.id')
            ->groupBy('rfc_threads.rfcSlug')
            ->select([
                'rfc_threads.rfcSlug',
                DB::raw('COUNT(DISTINCT rfc_threads.threadNumber) as threadCount'),
                DB::raw('MAX(threads.lastUpdate) as lastActivity'),
            ])
            ->get()
            ->mapWithKeys(fn(object $row) => [(string) $row->rfcSlug => new RfcActivity(
                threadCount: (int) $row->threadCount,
                lastActivity: $row->lastActivity !== null ? CarbonImmutable::parse($row->lastActivity) : null,
            )]);
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
