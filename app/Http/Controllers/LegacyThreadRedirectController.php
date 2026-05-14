<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Email\EmailRepository;
use App\Exceptions\NotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class LegacyThreadRedirectController extends Controller
{
    public function __construct(private readonly EmailRepository $emailRepository) {}

    public function __invoke(int $id): RedirectResponse
    {
        $threadSubject = DB::table('threads_old')->where('id', $id)->value('subject');
        if (! $threadSubject) {
            throw new NotFoundException('Thread not found');
        }

        $email = $this->emailRepository->findBySubject($threadSubject);

        return redirect("/message/{$email->number}", 301);
    }
}
