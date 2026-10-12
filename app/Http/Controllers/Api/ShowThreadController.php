<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\NotFoundException;
use App\Http\Controllers\Controller;
use App\Models\Email;
use App\Models\Thread;
use App\Services\Email\ThreadMarkdown;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShowThreadController extends Controller
{
    public function __invoke(int $number, Request $request, ThreadMarkdown $markdown): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $email = Email::where('number', $number)->first();
        if (! $email) {
            throw new NotFoundException("Email $number was not found");
        }

        if (! $email->isThreadRoot()) {
            $root = Email::find($email->threadId);
            if ($root) {
                // Return the page of the thread that contains this email
                $page = ThreadMarkdown::pageOf($markdown->emails($root), $number);

                return redirect("/api/threads/{$root->number}" . ($page > 1 ? "?page=$page" : ''));
            }
            // Root message not found — return the thread from this message anyway
        }

        $emails = $markdown->emails($email);
        $numbersById = $emails->pluck('number', 'id');
        $page = (int) ($validated['page'] ?? 1);
        $pageCount = ThreadMarkdown::pageCount($emails);
        if ($page > $pageCount) {
            throw new NotFoundException("Page $page of thread $number does not exist");
        }

        $messages = $emails->forPage($page, ThreadMarkdown::PER_PAGE)->values()->map(fn(Email $message) => [
            'number' => $message->number,
            'author' => $message->from->getNameOrEmail(),
            'date' => $message->date->format(DATE_ATOM),
            'inReplyTo' => $message->inReplyTo !== null ? $numbersById->get($message->inReplyTo) : null,
            'url' => url("/message/$number#{$message->number}"),
            'content' => $markdown->content($message),
        ]);

        return response()->json([
            'data' => [
                'number' => $number,
                'subject' => $email->subject,
                'author' => $email->from->getNameOrEmail(),
                'date' => $email->date->format(DATE_ATOM),
                'messageCount' => $emails->count(),
                'votes' => (int) Thread::where('emailId', $email->threadId)->value('votes'),
                'url' => url("/message/$number"),
                'markdownUrl' => url("/message/$number.md"),
                'archiveUrl' => "https://news-web.php.net/php.internals/$number",
                'messages' => $messages,
            ],
            'meta' => [
                'page' => $page,
                'perPage' => ThreadMarkdown::PER_PAGE,
                'pageCount' => $pageCount,
            ],
            'links' => [
                'prev' => $page > 1 ? url("/api/threads/$number?page=" . ($page - 1)) : null,
                'next' => $page < $pageCount ? url("/api/threads/$number?page=" . ($page + 1)) : null,
            ],
        ])->header('Cache-Control', 'public, max-age=300');
    }
}
