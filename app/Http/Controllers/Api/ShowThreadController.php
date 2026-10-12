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

class ShowThreadController extends Controller
{
    public function __invoke(int $number, ThreadMarkdown $markdown): JsonResponse|RedirectResponse
    {
        $email = Email::where('number', $number)->first();
        if (! $email) {
            throw new NotFoundException("Email $number was not found");
        }

        if (! $email->isThreadRoot()) {
            $root = Email::find($email->threadId);
            if ($root) {
                return redirect("/api/threads/{$root->number}");
            }
            // Root message not found — return the thread from this message anyway
        }

        $emails = Email::where('threadId', $email->threadId)->orderBy('date')->get();
        $numbersById = $emails->pluck('number', 'id');

        $messages = $emails->map(fn(Email $message) => [
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
        ])->header('Cache-Control', 'public, max-age=300');
    }
}
