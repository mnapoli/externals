<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Services\Search\EmailSearch;
use App\Services\Search\SearchResults;
use App\Support\Markdown\AgentLinks;
use Illuminate\Http\Response;

class SearchMarkdownController extends Controller
{
    public function __invoke(SearchRequest $request, EmailSearch $search): Response
    {
        $results = $request->results($search);
        if (! $results) {
            $markdown = "# Search\n\nSearch the PHP internals mailing list: `/search.md?q=your+query`."
                . " Optional parameters: `page`, `since` and `until` (YYYY-MM-DD).\n\n"
                . AgentLinks::markdown() . "\n";

            return $this->markdown($markdown);
        }

        $lines = [
            "# Search results for \"{$results->query}\"",
            '',
            "{$results->totalHits} results, page {$results->page} of " . max(1, $results->pageCount) . '.'
                . ' Each result is a message: its link is the whole thread as Markdown, find the message by its number.',
            '',
            AgentLinks::markdown(),
            '',
        ];
        foreach ($results->hits as $i => $hit) {
            $position = ($results->page - 1) * SearchResults::PER_PAGE + $i + 1;
            $lines[] = sprintf('%d. [%s](%s) — message #%d by %s on %s', $position, $hit->subject, url("/message/{$hit->number}.md"), $hit->number, $hit->author, $hit->date->toDateString());
            $lines[] = '   > ' . $hit->snippetAsMarkdown();
        }
        if ($results->page < $results->pageCount) {
            $lines[] = '';
            $lines[] = 'Next page: ' . $request->pageUrl('/search.md', $results->page + 1);
        }

        return $this->markdown(implode("\n", $lines) . "\n");
    }

    private function markdown(string $markdown): Response
    {
        return response($markdown, 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Cache-Control' => 'public, max-age=300',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
