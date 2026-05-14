<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the currently logged-in user from the session and exposes it as
 * $request->user() — without going through Laravel's Auth guard, since we
 * authenticate via GitHub OAuth rather than passwords.
 */
class ResolveSessionUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->session()->get('user_id');
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $request->setUserResolver(fn () => $user);
            }
        }

        return $next($request);
    }
}
