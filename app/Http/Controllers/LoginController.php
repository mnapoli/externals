<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\User\GetOrCreateUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class LoginController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect('/');
        }

        if (! $request->has('code')) {
            return Socialite::driver('github')->redirect();
        }

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
        $request->session()->put('user_id', $user->id);

        return redirect('/');
    }
}
