<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Email\EmailRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TopController extends Controller
{
    public function __construct(private readonly EmailRepository $repository) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('top', [
            'threads' => $this->repository->findTopThreads(1, $user),
            'user' => $user,
        ]);
    }
}
