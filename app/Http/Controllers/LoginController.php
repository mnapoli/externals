<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetOrCreateUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LoginController extends Controller
{
    public function __invoke(Request $request): Response
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
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect('/');
    }
}
