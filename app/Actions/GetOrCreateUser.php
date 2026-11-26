<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class GetOrCreateUser
{
    /**
     * @param  ?string  $githubEmail  Primary address of the GitHub account
     * @param  list<string>  $verifiedEmails  Every verified address of the GitHub account
     */
    public function handle(string $githubId, string $githubName, ?string $githubEmail = null, array $verifiedEmails = []): User
    {
        $user = User::where('githubId', $githubId)->first() ?? new User(['githubId' => $githubId]);
        $user->name = $githubName;
        // GitHub sends nothing when it could not list the addresses: keep the ones we have
        if ($githubEmail !== null && $githubEmail !== '') {
            $user->email = $githubEmail;
        }
        if (! $user->exists || $user->isDirty()) {
            $user->save();
        }

        if ($verifiedEmails !== []) {
            $this->replaceVerifiedEmails($user, $verifiedEmails);
        }

        return $user;
    }

    /**
     * @param  list<string>  $emails
     */
    private function replaceVerifiedEmails(User $user, array $emails): void
    {
        $emails = array_values(array_unique(array_map(fn(string $email) => mb_strtolower(mb_trim($email)), $emails)));

        DB::transaction(function () use ($user, $emails) {
            DB::table('user_verified_emails')->where('userId', $user->id)->delete();
            DB::table('user_verified_emails')->insert(array_map(fn(string $email) => ['userId' => $user->id, 'email' => $email], $emails));
        });
    }
}
