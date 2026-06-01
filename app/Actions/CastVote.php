<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Vote;
use Illuminate\Support\Carbon;

class CastVote
{
    /**
     * @return int The new total vote value for the email.
     */
    public function handle(int $userId, int $emailNumber, int $value): int
    {
        Vote::updateOrCreate(
            ['userId' => $userId, 'emailNumber' => $emailNumber],
            ['value' => $value, 'updatedAt' => Carbon::now('UTC')],
        );

        app(RefreshThread::class)->handle($emailNumber);

        return (int) Vote::where('emailNumber', $emailNumber)->sum('value');
    }
}
