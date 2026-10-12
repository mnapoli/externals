<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Services\Search\EmailSearch;
use App\Services\Search\SearchHit;
use App\Services\Search\SearchResults;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class SearchController extends Controller
{
    public function __invoke(SearchRequest $request, EmailSearch $search): JsonResponse
    {
        $results = $request->results($search);
        if (! $results) {
            throw ValidationException::withMessages(['q' => 'The q parameter is required.']);
        }

        return response()->json([
            'data' => array_map(fn(SearchHit $hit) => [
                'number' => $hit->number,
                'subject' => $hit->subject,
                'author' => $hit->author,
                'date' => $hit->date->toAtomString(),
                'snippet' => $hit->snippetAsText(),
                'url' => url("/message/{$hit->number}"),
                'markdownUrl' => url("/message/{$hit->number}.md"),
                'apiUrl' => url("/api/threads/{$hit->number}"),
            ], $results->hits),
            'meta' => [
                'query' => $results->query,
                'page' => $results->page,
                'perPage' => SearchResults::PER_PAGE,
                'pageCount' => $results->pageCount,
                'totalHits' => $results->totalHits,
            ],
            'links' => [
                'prev' => $results->page > 1 ? $request->pageUrl('/api/search', $results->page - 1) : null,
                'next' => $results->page < $results->pageCount ? $request->pageUrl('/api/search', $results->page + 1) : null,
            ],
        ])->header('Cache-Control', 'public, max-age=300')->header('X-Robots-Tag', 'noindex');
    }
}
