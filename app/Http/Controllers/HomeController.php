<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Email;
use App\Services\Email\ThreadQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __construct(
        private readonly ThreadQuery $threads,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $page = (int) $request->query('page', 1);
        $perPage = 20;

        $threadCount = Cache::remember(
            'stats.threadCount',
            now()->addMinutes(5),
            fn() => Email::where('isThreadRoot', true)->count(),
        );

        return view('home', [
            'threads' => $this->threads->findLatestThreads($page, $user),
            'page' => $page,
            'pageCount' => (int) ceil($threadCount / $perPage),
            'user' => $user,
        ]);
    }
}
