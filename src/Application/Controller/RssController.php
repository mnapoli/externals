<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Bref\Framework\Controller;
use Externals\Email\EmailRepository;
use Externals\RssBuilder;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RssController extends Controller
{
    public function __construct(
        private EmailRepository $emailRepository,
        private RssBuilder $rss,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $since = (int) $this->queryParameter($request, 'since', 0);
        $emails = $this->emailRepository->findLatest($since);

        return new Response(200, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ], $this->rss->build($emails));
    }
}
