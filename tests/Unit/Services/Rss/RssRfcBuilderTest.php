<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Rss;

use App\Services\Rss\RssRfcBuilder;
use DateTimeImmutable;
use SimpleXMLElement;
use Tests\TestCase;

class RssRfcBuilderTest extends TestCase
{
    public function test_should_build_valid_rss_with_no_threads(): void
    {
        $xml = (new RssRfcBuilder('https://externals.io'))->build([]);

        $rss = new SimpleXMLElement($xml);
        $this->assertSame('rss', $rss->getName());
        $this->assertSame('#externals - Latest RFC Threads', (string) $rss->channel->title);
        $this->assertSame('https://externals.io', (string) $rss->channel->link);
        $this->assertCount(0, $rss->channel->item);
    }

    public function test_should_build_item_for_each_thread(): void
    {
        $threads = [
            [
                'number' => 100,
                'subject' => '[RFC] My proposal',
                'date' => '2026-02-10 12:34:56',
            ],
            [
                'number' => 101,
                'subject' => '[RFC] Another proposal',
                'date' => '2026-02-11 09:00:00',
            ],
        ];

        $xml = (new RssRfcBuilder('https://externals.io'))->build($threads);

        $rss = new SimpleXMLElement($xml);
        $this->assertCount(2, $rss->channel->item);

        $first = $rss->channel->item[0];
        $this->assertSame('[RFC] My proposal', (string) $first->title);
        $this->assertSame('https://externals.io/message/100', (string) $first->link);
        $this->assertSame('[RFC] My proposal', (string) $first->description);
        $this->assertSame('100', (string) $first->guid);
        $this->assertSame((new DateTimeImmutable('2026-02-10 12:34:56'))->format('r'), (string) $first->pubDate);

        $second = $rss->channel->item[1];
        $this->assertSame('101', (string) $second->guid);
    }
}
