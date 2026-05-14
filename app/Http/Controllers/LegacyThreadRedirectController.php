<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\NotFoundException;
use App\Models\Email;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class LegacyThreadRedirectController extends Controller
{
    public function __invoke(int $id): RedirectResponse
    {
        $threadSubject = DB::table('threads_old')->where('id', $id)->value('subject');
        if (! $threadSubject) {
            throw new NotFoundException('Thread not found');
        }

        $email = Email::where('subject', $threadSubject)
            ->where('isThreadRoot', true)
            ->orderBy('date', 'desc')
            ->first();
        if (! $email) {
            throw new NotFoundException('Email not found');
        }

        return redirect("/message/{$email->number}", 301);
    }
}
