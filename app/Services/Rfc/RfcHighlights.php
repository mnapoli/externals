<?php

declare(strict_types=1);

namespace App\Services\Rfc;

use App\Models\Rfc;
use App\Models\RfcPoll;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Throwable;

/**
 * The RFCs worth surfacing on the home page: open votes, then the most active discussions.
 */
class RfcHighlights
{
    public function __construct(
        private readonly RfcThreads $rfcThreads,
    ) {}

    /**
     * e.g. "Closes in 3 days", "Closed 2 hours ago", or null when the wiki gives no date we can read.
     */
    public static function closesLabel(?RfcPoll $poll): ?string
    {
        $closesAt = self::closesAt($poll);
        if ($closesAt === null) {
            return null;
        }

        return $closesAt->isFuture()
            ? 'Closes in ' . $closesAt->diffForHumans(syntax: CarbonInterface::DIFF_ABSOLUTE)
            : 'Closed ' . $closesAt->diffForHumans();
    }

    public static function isPassing(RfcPoll $poll, float $yesShare): bool
    {
        return $yesShare >= $poll->majority();
    }

    public static function closesAt(?RfcPoll $poll): ?CarbonImmutable
    {
        if ($poll?->closesAt === null) {
            return null;
        }
        try {
            return CarbonImmutable::parse($poll->closesAt);
        } catch (Throwable) {
            // Free text copied from the wiki
            return null;
        }
    }

    /**
     * Share of "Yes" among the Yes and No votes of a poll, between 0 and 1, or null without votes.
     */
    public static function yesShare(RfcPoll $poll): ?float
    {
        $yes = 0;
        $no = 0;
        foreach ($poll->choices as $choice) {
            match (mb_strtolower($choice['label'])) {
                'yes' => $yes += $choice['count'],
                'no' => $no += $choice['count'],
                default => null,
            };
        }

        return $yes + $no > 0 ? $yes / ($yes + $no) : null;
    }

    /**
     * RFCs with an open vote, the closest deadline first.
     *
     * @return Collection<int, Rfc>
     */
    public function voting(): Collection
    {
        return Rfc::where('status', RfcStatus::Voting)
            ->with('polls')
            ->get()
            ->filter(fn(Rfc $rfc) => $rfc->polls->contains(fn(RfcPoll $poll) => ! $poll->isClosed))
            ->sortBy(fn(Rfc $rfc) => self::closesAt($rfc->currentPoll())?->getTimestamp() ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * RFCs under discussion, the most recently discussed first.
     *
     * @return Collection<int, array{rfc: Rfc, activity: RfcActivity}>
     */
    public function inDiscussion(int $limit): Collection
    {
        $activity = $this->rfcThreads->activityByRfc()
            ->filter(fn(RfcActivity $activity) => $activity->lastActivity !== null)
            ->sortByDesc(fn(RfcActivity $activity) => $activity->lastActivity?->getTimestamp());

        $rfcs = Rfc::where('status', RfcStatus::Discussion)
            ->whereIn('slug', $activity->keys()->all())
            ->get()
            ->keyBy('slug');

        $highlights = [];
        foreach ($activity as $slug => $rfcActivity) {
            $rfc = $rfcs->get($slug);
            if ($rfc !== null) {
                $highlights[] = ['rfc' => $rfc, 'activity' => $rfcActivity];
            }
        }

        return collect(array_slice($highlights, 0, $limit));
    }
}
