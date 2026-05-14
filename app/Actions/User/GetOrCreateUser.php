<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;

class GetOrCreateUser
{
    public function handle(string $githubId, string $githubName): User
    {
        $user = User::where('githubId', $githubId)->first();

        if ($user) {
            if ($user->name !== $githubName) {
                $user->name = $githubName;
                $user->save();
            }

            return $user;
        }

        return User::create([
            'githubId' => $githubId,
            'name' => $githubName,
        ]);
    }
}
