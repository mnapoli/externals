<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\GetOrCreateUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as OAuthTwoUser;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GithubController extends Controller
{
    /**
     * Shown once, to accounts that predate the storage of emails, at the login that stores their address.
     * The footer of every page says the same thing permanently.
     */
    public const string EMAIL_STORED_MESSAGE = 'Externals now stores the email addresses of your GitHub account (%s). They are used to show your GitHub avatar next to the emails you send to the mailing list, and are never shared or sold.';

    /**
     * GitHub redirects back to this same URL after authorization, so this
     * entry point handles both phases: sending the user to GitHub, and
     * processing the callback once GitHub returns with a `code`.
     */
    public function __invoke(Request $request): Response
    {
        if ($request->user()) {
            return redirect('/');
        }

        if (! $request->has('code')) {
            return Socialite::driver('github')->redirect();
        }

        return $this->handleCallback($request);
    }

    /**
     * Handle the callback from GitHub after authorization.
     */
    private function handleCallback(Request $request): Response
    {
        try {
            $githubUser = Socialite::driver('github')->user();
        } catch (InvalidStateException) {
            return response()->view('auth.login-error', ['error' => 'Invalid state'], 400);
        } catch (Throwable $e) {
            return response()->view('auth.login-error', ['error' => $e->getMessage()], 400);
        }

        $user = app(GetOrCreateUser::class)->handle(
            (string) $githubUser->getId(),
            (string) $githubUser->getNickname(),
            $githubUser->getEmail(),
            $this->verifiedEmails($githubUser instanceof OAuthTwoUser ? $githubUser->token : null),
        );
        // Read before Auth::login(), which saves the remember token and resets wasChanged()
        $emailJustStored = ! $user->wasRecentlyCreated && $user->wasChanged('email') && $user->getPrevious()['email'] === null;

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        if ($emailJustStored) {
            $request->session()->flash('status', sprintf(self::EMAIL_STORED_MESSAGE, $user->email));
        }

        return redirect('/');
    }

    /**
     * The address used on the mailing list is often not the primary one of the GitHub account.
     *
     * @return list<string> Empty if GitHub could not list them
     */
    private function verifiedEmails(?string $token): array
    {
        if ($token === null || $token === '') {
            return [];
        }
        try {
            $response = Http::withToken($token)->acceptJson()->timeout(5)->get('https://api.github.com/user/emails');
        } catch (Throwable) {
            return [];
        }
        if (! $response->successful() || ! is_array($response->json())) {
            return [];
        }

        $emails = [];
        foreach ($response->json() as $email) {
            if (is_array($email) && ($email['verified'] ?? false) === true && is_string($email['email'] ?? null)) {
                $emails[] = $email['email'];
            }
        }

        return $emails;
    }
}
