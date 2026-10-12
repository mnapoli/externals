<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Services\Search\EmailSearch;
use Illuminate\Http\Response;

/**
 * Server-side search, so that it works without JavaScript (and for agents).
 */
class SearchController extends Controller
{
    public function __invoke(SearchRequest $request, EmailSearch $search): Response
    {
        $results = $request->results($search);

        return response()->view('search', [
            'query' => $request->searchQuery(),
            'results' => $results,
            'previousUrl' => $results && $results->page > 1 ? $request->pageUrl('/search', $results->page - 1) : null,
            'nextUrl' => $results && $results->page < $results->pageCount ? $request->pageUrl('/search', $results->page + 1) : null,
            'markdownUrl' => $results ? $request->pageUrl('/search.md', $results->page) : null,
        ])->header('X-Robots-Tag', 'noindex');
    }
}
