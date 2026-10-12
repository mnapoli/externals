<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Email;
use App\Services\Email\ThreadQuery;
use App\Support\Email\ThreadSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ListThreadsController extends Controller
{
    private const int PER_PAGE = 20;

    public function __invoke(Request $request, ThreadQuery $threads): JsonResponse
    {
        $validated = $request->validate([
            'sort' => ['sometimes', 'in:latest,top'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $sort = $validated['sort'] ?? 'latest';
        $page = (int) ($validated['page'] ?? 1);

        if ($sort === 'top') {
            // Same as the "Top" page: a single page of the most voted recent threads
            $results = $page === 1 ? $threads->findTopThreads(1, null) : [];
            $pageCount = 1;
        } else {
            $results = $threads->findLatestThreads($page, null);
            $threadCount = Cache::remember(
                'stats.threadCount',
                now()->addMinutes(5),
                fn() => Email::where('isThreadRoot', true)->count(),
            );
            $pageCount = (int) ceil($threadCount / self::PER_PAGE);
        }

        return response()->json([
            'data' => array_map($this->thread(...), $results),
            'meta' => [
                'sort' => $sort,
                'page' => $page,
                'perPage' => self::PER_PAGE,
                'pageCount' => $pageCount,
            ],
            'links' => [
                'prev' => $page > 1 ? url('/api/threads?' . http_build_query(['sort' => $sort, 'page' => $page - 1])) : null,
                'next' => $page < $pageCount ? url('/api/threads?' . http_build_query(['sort' => $sort, 'page' => $page + 1])) : null,
            ],
        ])->header('Cache-Control', 'public, max-age=60');
    }

    /**
     * @return array<string, mixed>
     */
    private function thread(ThreadSummary $thread): array
    {
        return [
            'number' => $thread->number,
            'subject' => $thread->subject,
            'author' => $thread->fromName ?: $thread->fromEmail,
            'date' => $thread->date->toAtomString(),
            'lastActivity' => $thread->lastUpdate->toAtomString(),
            'messageCount' => $thread->emailCount,
            'votes' => $thread->votes,
            'url' => url("/message/{$thread->number}"),
            'markdownUrl' => url("/message/{$thread->number}.md"),
            'apiUrl' => url("/api/threads/{$thread->number}"),
        ];
    }
}
