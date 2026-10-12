<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Controllers\ThreadMarkdownController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the Markdown version of a thread when the client prefers it
 * over HTML (e.g. `Accept: text/markdown`), as LLM agents often do.
 */
class ServeThreadMarkdown
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->prefers(['text/html', 'text/markdown']) === 'text/markdown') {
            return app()->call(ThreadMarkdownController::class, ['number' => (int) $request->route('number')]);
        }

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('Vary', 'Accept');

        return $response;
    }
}
