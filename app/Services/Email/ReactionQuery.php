<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Models\EmailReaction;
use App\Support\Reaction;

class ReactionQuery
{
    /**
     * Reaction counts of each email, in the order of the picker, and the reactions of the user.
     *
     * Reactions are not used as array keys: PHP would turn "-1" into an integer.
     *
     * @param  list<int>  $emailNumbers
     * @return array<int, array{counts: list<array{reaction: string, count: int}>, mine: list<string>}> Keyed by email number, only emails with reactions
     */
    public function forEmails(array $emailNumbers, ?int $userId): array
    {
        if ($emailNumbers === []) {
            return [];
        }

        $rows = EmailReaction::query()
            ->whereIn('emailNumber', $emailNumbers)
            ->orderBy('createdAt')
            ->get(['userId', 'emailNumber', 'reaction'])
            ->groupBy('emailNumber');

        $reactions = [];
        foreach ($rows as $emailNumber => $emailRows) {
            $counts = [];
            $mine = [];
            foreach (Reaction::cases() as $reaction) {
                $count = $emailRows->filter(fn(EmailReaction $row) => $row->reaction === $reaction)->count();
                if ($count > 0) {
                    $counts[] = ['reaction' => $reaction->value, 'count' => $count];
                }
            }
            foreach ($emailRows as $row) {
                if ($userId !== null && $row->userId === $userId) {
                    $mine[] = $row->reaction->value;
                }
            }
            $reactions[(int) $emailNumber] = ['counts' => $counts, 'mine' => $mine];
        }

        return $reactions;
    }
}
