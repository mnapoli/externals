<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Laravel\Socialite\Two\GithubProvider as SocialiteGithubProvider;

/**
 * Leaves the addresses of the account to GithubController, which lists all of them rather than only the primary one.
 * That saves the second call to the same GitHub endpoint at each login.
 */
final class GithubProvider extends SocialiteGithubProvider
{
    /**
     * @param  string  $token
     */
    protected function getEmailByToken($token): ?string
    {
        return null;
    }
}
