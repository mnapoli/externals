<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Entry point of the API: lists the endpoints so that agents can discover them.
 */
class ApiIndexController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'name' => 'externals.io API',
            'description' => 'Read-only API over the php.internals mailing list, for AI agents and personal tools. No authentication.',
            'usage' => 'Please do not bulk download the archive through this API. The original archive is https://news-web.php.net/php.internals',
            'documentation' => url('/llms.txt'),
            'endpoints' => [
                [
                    'url' => url('/api/threads'),
                    'description' => 'Latest or top threads, 20 per page.',
                    'parameters' => ['sort' => 'latest (default) or top', 'page' => 'page number, from 1'],
                ],
                [
                    'url' => url('/api/threads/{number}'),
                    'description' => 'A thread with all its messages, content as Markdown. A reply number redirects to its thread.',
                ],
                [
                    'url' => url('/api/search'),
                    'description' => 'Full-text search over emails, 20 results per page.',
                    'parameters' => ['q' => 'search query (required)', 'page' => 'page number, from 1', 'since' => 'YYYY-MM-DD', 'until' => 'YYYY-MM-DD'],
                ],
                [
                    'url' => url('/api/rfcs'),
                    'description' => 'All RFCs of wiki.php.net, in the order of the wiki index.',
                    'parameters' => ['status' => 'voting, discussion, draft, accepted, implemented, declined, withdrawn, inactive or obsolete'],
                ],
                [
                    'url' => url('/api/rfcs/{slug}'),
                    'description' => 'An RFC with its votes, the threads that discuss it, and its text as Markdown.',
                ],
            ],
            'markdown' => [
                'thread' => url('/message/{number}.md'),
                'search' => url('/search.md?q={query}'),
                'rfcs' => url('/rfc.md'),
                'rfc' => url('/rfc/{slug}.md'),
            ],
        ])->header('Cache-Control', 'public, max-age=3600');
    }
}
