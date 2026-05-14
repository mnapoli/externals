<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Email;
use App\Services\Email\ThreadQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(private readonly ThreadQuery $threads) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $page = (int) $request->query('page', 1);
        $perPage = 20;

        return view('home', [
            'threads' => $this->threads->findLatestThreads($page, $user),
            'page' => $page,
            'pageCount' => (int) ceil(Email::where('isThreadRoot', true)->count() / $perPage),
            'user' => $user,
        ]);
    }
}
