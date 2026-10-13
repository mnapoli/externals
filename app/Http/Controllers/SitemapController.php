<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\NotFoundException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SitemapController extends Controller
{
    /** The sitemap protocol allows up to 50,000 URLs per file; stay well below to keep responses light. */
    public const int THREADS_PER_SITEMAP = 10_000;

    public function index(): Response
    {
        $threadCount = DB::table('threads')->count();
        $pageCount = max(1, (int) ceil($threadCount / self::THREADS_PER_SITEMAP));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= $this->entry('sitemap', url('/sitemaps/pages.xml'));
        for ($page = 1; $page <= $pageCount; $page++) {
            $xml .= $this->entry('sitemap', url("/sitemaps/threads-$page.xml"));
        }
        $xml .= '</sitemapindex>' . "\n";

        return $this->xmlResponse($xml);
    }

    public function pages(): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= $this->entry('url', url('/'));
        $xml .= $this->entry('url', url('/top'));
        // Not the RFC pages: their canonical URL is on wiki.php.net
        $xml .= $this->entry('url', url('/rfc'));
        $xml .= '</urlset>' . "\n";

        return $this->xmlResponse($xml);
    }

    public function threads(int $page): Response
    {
        if ($page < 1) {
            throw new NotFoundException("Sitemap page $page was not found");
        }

        $threads = DB::table('threads')
            ->select(['emailNumber', 'lastUpdate'])
            ->orderBy('emailNumber')
            ->offset(($page - 1) * self::THREADS_PER_SITEMAP)
            ->limit(self::THREADS_PER_SITEMAP)
            ->get();

        // An empty first page is valid: the index always lists it
        if ($threads->isEmpty() && $page > 1) {
            throw new NotFoundException("Sitemap page $page was not found");
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($threads as $thread) {
            $lastModified = CarbonImmutable::parse($thread->lastUpdate)->format('Y-m-d');
            $xml .= $this->entry('url', url("/message/{$thread->emailNumber}"), $lastModified);
        }
        $xml .= '</urlset>' . "\n";

        return $this->xmlResponse($xml);
    }

    private function entry(string $tag, string $location, ?string $lastModified = null): string
    {
        $entry = "<$tag><loc>" . htmlspecialchars($location, ENT_XML1) . '</loc>';
        if ($lastModified !== null) {
            $entry .= "<lastmod>$lastModified</lastmod>";
        }

        return $entry . "</$tag>\n";
    }

    private function xmlResponse(string $xml): Response
    {
        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
