<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\NotFoundException;
use App\Models\Email;
use App\Services\Email\ThreadMarkdown;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class ThreadMarkdownController extends Controller
{
    public function __invoke(int $number, ThreadMarkdown $markdown): Response|RedirectResponse
    {
        $email = Email::where('number', $number)->first();
        if (! $email) {
            throw new NotFoundException("Email $number was not found");
        }

        if (! $email->isThreadRoot()) {
            $root = Email::find($email->threadId);
            if ($root) {
                return redirect("/message/{$root->number}.md");
            }
            // Root message not found — render the thread from this message anyway
        }

        return response($markdown->render($email), 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Cache-Control' => 'public, max-age=300',
            'Link' => '<' . url("/message/$number") . '>; rel="canonical"',
            'Vary' => 'Accept',
        ]);
    }
}
