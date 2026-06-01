<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StatsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $ttl = now()->addMinutes(5);

        return view('stats', [
            'userCount' => Cache::remember('stats.userCount', $ttl, fn() => User::count()),
            'threadCount' => Cache::remember('stats.threadCount', $ttl, fn() => Email::where('isThreadRoot', true)->count()),
            'emailCount' => Cache::remember('stats.emailCount', $ttl, fn() => Email::count()),
            'user' => $request->user(),
        ]);
    }
}
