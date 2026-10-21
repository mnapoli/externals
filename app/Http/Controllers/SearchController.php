<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Models\Email;
use App\Services\Email\AvatarUrls;
use App\Services\Search\EmailSearch;
use App\Services\Search\SearchHit;
use Illuminate\Http\Response;

/**
 * Server-side search, so that it works without JavaScript (and for agents).
 */
class SearchController extends Controller
{
    public function __invoke(SearchRequest $request, EmailSearch $search, AvatarUrls $avatarUrls): Response
    {
        $results = $request->results($search);

        // The search index has the name of the authors, not their address
        $authorEmails = $results
            ? Email::whereIn('number', array_map(fn(SearchHit $hit) => $hit->number, $results->hits))->pluck('fromEmail', 'number')
            : collect();
        $avatars = $avatarUrls->of($authorEmails->values());

        return response()->view('search', [
            'query' => $request->searchQuery(),
            'results' => $results,
            'avatars' => $authorEmails->map(fn(?string $email) => $avatars[$email ?? ''] ?? null)->all(),
            'previousUrl' => $results && $results->page > 1 ? $request->pageUrl('/search', $results->page - 1) : null,
            'nextUrl' => $results && $results->page < $results->pageCount ? $request->pageUrl('/search', $results->page + 1) : null,
            'markdownUrl' => $results ? $request->pageUrl('/search.md', $results->page) : null,
        ])->header('X-Robots-Tag', 'noindex');
    }
}
