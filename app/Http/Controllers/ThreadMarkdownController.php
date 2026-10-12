<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\NotFoundException;
use App\Models\Email;
use App\Services\Email\ThreadMarkdown;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ThreadMarkdownController extends Controller
{
    public function __invoke(int $number, Request $request, ThreadMarkdown $markdown): Response|RedirectResponse
    {
        $email = Email::where('number', $number)->first();
        if (! $email) {
            throw new NotFoundException("Email $number was not found");
        }

        if (! $email->isThreadRoot()) {
            $root = Email::find($email->threadId);
            if ($root) {
                // Open the thread on the page that contains this email
                $page = ThreadMarkdown::pageOf($markdown->emails($root), $number);

                return redirect("/message/{$root->number}.md" . ($page > 1 ? "?page=$page" : ''));
            }
            // Root message not found — render the thread from this message anyway
        }

        $page = filter_var($request->query('page', '1'), FILTER_VALIDATE_INT);
        if ($page === false) {
            throw new NotFoundException('Invalid page');
        }

        return response($markdown->render($email, $page), 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Cache-Control' => 'public, max-age=300',
            'Link' => '<' . url("/message/$number") . '>; rel="canonical"',
            'Vary' => 'Accept',
        ]);
    }
}
