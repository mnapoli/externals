<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Bref\Micro\Controller;
use Externals\Email\EmailRepository;
use Externals\RssRfcBuilder;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RssRfcController extends Controller
{
    public function __construct(
        private EmailRepository $emailRepository,
        private RssRfcBuilder $rss,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $emails = $this->emailRepository->findLatestRfcThreads();

        return new Response(200, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ], $this->rss->build($emails));
    }
}
