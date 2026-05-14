<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Email\EmailRepository;
use App\Exceptions\NotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ThreadController extends Controller
{
    public function __construct(private readonly EmailRepository $repository) {}

    public function __invoke(Request $request, int $number): Response|RedirectResponse
    {
        $email = $this->repository->getByNumber($number);
        if (! $email->isThreadRoot()) {
            try {
                $thread = $this->repository->getById($email->threadId);

                return redirect("/message/{$thread->number}#$number");
            } catch (NotFoundException) {
                // Root message not found — render the thread from this URL anyway
            }
        }

        $user = $request->user();
        // Build the thread view BEFORE marking the thread as read
        $threadView = $this->repository->getThreadView($email, $user);
        if ($user) {
            $this->repository->markAsRead($email, $user);
        }

        return response()->view('thread', [
            'subject' => $email->subject,
            'thread' => $threadView,
            'threadId' => $number,
            'user' => $user,
        ]);
    }
}
