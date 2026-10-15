<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Support\Email\EmailAddress;
use Illuminate\Support\Facades\DB;

class AvatarUrls
{
    /**
     * The GitHub avatar of the people who logged in with an account where the address is verified,
     * Gravatar otherwise (transparent when there is no photo, so that the initials underneath show through).
     *
     * @param  iterable<?string>  $emails
     * @return array<string, string> Keyed by the addresses as given
     */
    public function of(iterable $emails, int $size = 64): array
    {
        $normalized = [];
        foreach ($emails as $email) {
            if ($email !== null && $email !== '') {
                $normalized[$email] = mb_strtolower(mb_trim($email));
            }
        }
        if ($normalized === []) {
            return [];
        }

        $githubIds = DB::table('user_verified_emails')
            ->join('users', 'users.id', '=', 'user_verified_emails.userId')
            ->whereIn('user_verified_emails.email', array_values(array_unique($normalized)))
            ->pluck('users.githubId', 'user_verified_emails.email');

        $urls = [];
        foreach ($normalized as $email => $key) {
            $githubId = $githubIds[$key] ?? null;
            $urls[$email] = is_numeric($githubId)
                ? "https://avatars.githubusercontent.com/u/{$githubId}?s={$size}&v=4"
                : (string) EmailAddress::avatarUrlOf($email, $size);
        }

        return $urls;
    }
}
