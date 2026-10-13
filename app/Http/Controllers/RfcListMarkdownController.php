<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Rfc\RfcMarkdown;
use Illuminate\Http\Response;

class RfcListMarkdownController extends Controller
{
    public function __invoke(RfcMarkdown $markdown): Response
    {
        return response($markdown->renderList(), 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Cache-Control' => 'public, max-age=300',
            'Link' => '<' . url('/rfc') . '>; rel="canonical"',
            'Vary' => 'Accept',
        ]);
    }
}
