<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Email\EmailRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(private readonly EmailRepository $repository) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $page = (int) $request->query('page', 1);
        $perPage = 20;

        return view('home', [
            'threads' => $this->repository->findLatestThreads($page, $user),
            'page' => $page,
            'pageCount' => (int) ceil($this->repository->getThreadCount() / $perPage),
            'user' => $user,
        ]);
    }
}
