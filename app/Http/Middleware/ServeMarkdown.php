<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the Markdown version of a page when the client prefers it over
 * HTML (e.g. `Accept: text/markdown`), as LLM agents often do.
 *
 * Usage: `->middleware(ServeMarkdown::class . ':' . MarkdownController::class)`.
 * The controller receives the parameters of the route.
 */
class ServeMarkdown
{
    public function handle(Request $request, Closure $next, string $markdownController): Response
    {
        if ($request->prefers(['text/html', 'text/markdown']) === 'text/markdown') {
            return app()->call($markdownController, $request->route()?->parameters() ?? []);
        }

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('Vary', 'Accept');

        return $response;
    }
}
