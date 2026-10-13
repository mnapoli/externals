<?php

declare(strict_types=1);

namespace App\Services\Rfc;

use DateTimeImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

/**
 * Reads RFCs from https://wiki.php.net (a DokuWiki).
 */
class WikiClient
{
    /**
     * DokuWiki source of the RFC index page.
     */
    public function index(): string
    {
        return $this->request()->get(RfcPageParser::WIKI_URL . '/rfc', ['do' => 'export_raw'])->throw()->body();
    }

    /**
     * Rendered HTML of an RFC page, without the wiki layout.
     */
    public function page(string $slug): string
    {
        return $this->request()->get(RfcPageParser::WIKI_URL . '/rfc/' . $slug, ['do' => 'export_xhtmlbody'])->throw()->body();
    }

    /**
     * Latest changes of RFC pages.
     *
     * @return array<string, DateTimeImmutable> Date of the last change, by RFC slug
     */
    public function recentChanges(int $count = 50): array
    {
        $xml = $this->request()
            ->get(RfcPageParser::WIKI_URL . '/feed.php', ['ns' => 'rfc', 'num' => $count, 'linkto' => 'page', 'content' => 'abstract'])
            ->throw()
            ->body();

        $feed = new SimpleXMLElement($xml);
        $feed->registerXPathNamespace('rss', 'http://purl.org/rss/1.0/');
        $feed->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');

        $changes = [];
        foreach ($feed->xpath('//rss:item') ?: [] as $item) {
            $item->registerXPathNamespace('rss', 'http://purl.org/rss/1.0/');
            $item->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
            $link = (string) ($item->xpath('rss:link')[0] ?? '');
            $date = (string) ($item->xpath('dc:date')[0] ?? '');
            if (! preg_match('#/rfc/([^?\#]+)#', $link, $matches) || $date === '') {
                continue;
            }
            $slug = RfcLinkExtractor::normalizeSlug($matches[1]);
            $date = new DateTimeImmutable($date);
            // Items are sorted by date: keep the most recent change of each page
            $changes[$slug] ??= $date;
        }

        return $changes;
    }

    private function request(): PendingRequest
    {
        return Http::withUserAgent('externals.io (+https://externals.io)')->timeout(20)->retry(2, 1000);
    }
}
