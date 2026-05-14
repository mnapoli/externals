<?php

declare(strict_types=1);

namespace App\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    public function getOrCreate(string $githubId, string $ghName): User
    {
        $user = User::where('githubId', $githubId)->first();

        if ($user) {
            if ($user->name !== $ghName) {
                $user->name = $ghName;
                $user->save();
            }

            return $user;
        }

        return User::create([
            'githubId' => $githubId,
            'name' => $ghName,
        ]);
    }

    public function getUserCount(): int
    {
        return (int) DB::table('users')->count();
    }
}
