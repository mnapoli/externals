<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Email\EmailRepository;
use App\Rss\RssBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RssController extends Controller
{
    public function __construct(
        private readonly EmailRepository $emailRepository,
        private readonly RssBuilder $rss,
    ) {}

    public function __invoke(Request $request): Response
    {
        $since = (int) $request->query('since', 0);
        $emails = $this->emailRepository->findLatest($since);

        return response($this->rss->build($emails), 200, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }
}
