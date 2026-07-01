<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\GetOrCreateUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GithubController extends Controller
{
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
        );
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect('/');
    }
}
