<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Email\ThreadQuery;
use App\Services\Rss\RssRfcBuilder;
use Illuminate\Http\Response;

class RssRfcController extends Controller
{
    public function __construct(
        private readonly ThreadQuery $threads,
        private readonly RssRfcBuilder $rss,
    ) {}

    public function __invoke(): Response
    {
        return response($this->rss->build($this->threads->findLatestRfcThreads()), 200, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }
}
