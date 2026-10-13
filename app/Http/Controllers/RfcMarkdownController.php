<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\NotFoundException;
use App\Models\Rfc;
use App\Services\Rfc\RfcLinkExtractor;
use App\Services\Rfc\RfcMarkdown;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class RfcMarkdownController extends Controller
{
    public function __invoke(string $slug, RfcMarkdown $markdown): Response|RedirectResponse
    {
        $normalized = RfcLinkExtractor::normalizeSlug($slug);
        $rfc = Rfc::with('polls')->where('slug', $normalized)->first();
        if (! $rfc) {
            throw new NotFoundException("RFC $slug was not found");
        }
        if ($normalized !== $slug) {
            return redirect("/rfc/$normalized.md");
        }

        return response($markdown->render($rfc), 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Cache-Control' => 'public, max-age=300',
            'Link' => '<' . $rfc->wikiUrl() . '>; rel="canonical"',
            'Vary' => 'Accept',
        ]);
    }
}
