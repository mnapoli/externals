<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Rfc;
use App\Models\RfcVote;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class CastRfcVote
{
    /**
     * @param  int  $value  1, -1, or 0 to remove the vote
     */
    public function handle(int $userId, Rfc $rfc, int $value): void
    {
        if (! in_array($value, [-1, 0, 1], true)) {
            throw new InvalidArgumentException("Invalid vote value $value");
        }
        if (! $rfc->status->acceptsCommunityVotes()) {
            throw new InvalidArgumentException("RFC {$rfc->slug} is closed to community votes");
        }

        if ($value === 0) {
            RfcVote::where('userId', $userId)->where('rfcId', $rfc->id)->delete();

            return;
        }

        RfcVote::updateOrCreate(
            ['userId' => $userId, 'rfcId' => $rfc->id],
            ['value' => $value, 'updatedAt' => Carbon::now('UTC')],
        );
    }
}
