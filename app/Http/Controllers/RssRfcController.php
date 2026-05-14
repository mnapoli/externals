<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Email\EmailRepository;
use App\Rss\RssRfcBuilder;
use Illuminate\Http\Response;

class RssRfcController extends Controller
{
    public function __construct(
        private readonly EmailRepository $emailRepository,
        private readonly RssRfcBuilder $rss,
    ) {}

    public function __invoke(): Response
    {
        $threads = $this->emailRepository->findLatestRfcThreads();

        return response($this->rss->build($threads), 200, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }
}
