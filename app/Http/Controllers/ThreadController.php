<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Email\MarkEmailAsRead;
use App\Exceptions\NotFoundException;
use App\Models\Email;
use App\Services\Email\ThreadQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ThreadController extends Controller
{
    public function __construct(private readonly ThreadQuery $threads) {}

    public function __invoke(Request $request, int $number): Response|RedirectResponse
    {
        $email = Email::where('number', $number)->first();
        if (! $email) {
            throw new NotFoundException("Email $number was not found");
        }

        if (! $email->isThreadRoot()) {
            $thread = Email::find($email->threadId);
            if ($thread) {
                return redirect("/message/{$thread->number}#$number");
            }
            // Root message not found — render the thread from this URL anyway
        }

        $user = $request->user();
        // Build the thread view BEFORE marking the thread as read
        $threadView = $this->threads->getThreadView($email, $user);
        if ($user) {
            app(MarkEmailAsRead::class)->handle($email, $user);
        }

        return response()->view('thread', [
            'subject' => $email->subject,
            'thread' => $threadView,
            'threadId' => $number,
            'user' => $user,
        ]);
    }
}
