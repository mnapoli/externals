<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Email\EmailRepository;
use App\User\UserRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function __construct(
        private readonly EmailRepository $emailRepository,
        private readonly UserRepository $userRepository,
    ) {}

    public function __invoke(Request $request): View
    {
        return view('stats', [
            'userCount' => $this->userRepository->getUserCount(),
            'threadCount' => $this->emailRepository->getThreadCount(),
            'emailCount' => $this->emailRepository->getEmailCount(),
            'user' => $request->user(),
        ]);
    }
}
