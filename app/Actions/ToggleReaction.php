<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\EmailReaction;
use App\Support\Reaction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

class ToggleReaction
{
    /**
     * Adds the reaction of the user on the email, or removes it if it was already there.
     *
     * @return bool Whether the user now has this reaction on the email.
     */
    public function handle(int $userId, int $emailNumber, Reaction $reaction): bool
    {
        $removed = EmailReaction::where('userId', $userId)
            ->where('emailNumber', $emailNumber)
            ->where('reaction', $reaction->value)
            ->delete();
        if ($removed > 0) {
            return false;
        }

        try {
            EmailReaction::create([
                'userId' => $userId,
                'emailNumber' => $emailNumber,
                'reaction' => $reaction,
                'createdAt' => Carbon::now('UTC'),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Double click: the other request added it already
        }

        return true;
    }
}
