<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\NotFoundException;
use App\Http\Controllers\Controller;
use App\Models\Rfc;
use App\Models\RfcPoll;
use App\Services\Rfc\LinkedThread;
use App\Services\Rfc\RfcContent;
use App\Services\Rfc\RfcLinkExtractor;
use App\Services\Rfc\RfcThreads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ShowRfcController extends Controller
{
    public function __invoke(string $slug, RfcThreads $threads, RfcContent $content): JsonResponse|RedirectResponse
    {
        $normalized = RfcLinkExtractor::normalizeSlug($slug);
        $rfc = Rfc::with('polls')->where('slug', $normalized)->first();
        if (! $rfc) {
            throw new NotFoundException("RFC $slug was not found");
        }
        if ($normalized !== $slug) {
            return redirect("/api/rfcs/$normalized");
        }

        return response()->json([
            'data' => [
                'slug' => $rfc->slug,
                'title' => $rfc->title,
                'status' => $rfc->status->value,
                'section' => $rfc->indexSubsection,
                'phpVersion' => $rfc->phpVersion,
                'isListed' => $rfc->isListed,
                'version' => $rfc->version,
                'date' => $rfc->date,
                'authors' => $rfc->authors,
                'rawStatus' => $rfc->rawStatus,
                'targetVersion' => $rfc->targetVersion,
                'implementation' => $rfc->implementation,
                'url' => $rfc->url(),
                'markdownUrl' => $rfc->url() . '.md',
                'wikiUrl' => $rfc->wikiUrl(),
                'fetchedAt' => $rfc->fetchedAt?->format(DATE_ATOM),
                'polls' => $rfc->polls->map(fn(RfcPoll $poll) => [
                    'question' => $poll->question,
                    'isClosed' => $poll->isClosed,
                    'closesAt' => $poll->closesAt,
                    'choices' => $poll->choices,
                    'voters' => $poll->voters,
                ]),
                'threads' => $threads->threadsOf($rfc->slug)->map(fn(LinkedThread $thread) => [
                    'number' => $thread->number,
                    'subject' => $thread->subject,
                    'date' => $thread->date->toAtomString(),
                    'messageCount' => $thread->emailCount,
                    'url' => url("/message/{$thread->number}"),
                    'markdownUrl' => url("/message/{$thread->number}.md"),
                    'apiUrl' => url("/api/threads/{$thread->number}"),
                ]),
                // Markdown, converted from the wiki
                'content' => $rfc->content !== null ? $content->markdown($rfc) : null,
            ],
        ])->header('Cache-Control', 'public, max-age=300');
    }
}
