<?php

declare(strict_types=1);

use App\Services\Rss\RssRfcBuilder;
use App\Support\Email\ThreadSummary;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use SimpleXMLElement;

test('should build valid rss with no threads', function (): void {
    $xml = (new RssRfcBuilder('https://externals.io'))->build([]);

    $rss = new SimpleXMLElement($xml);
    $this->assertSame('rss', $rss->getName());
    $this->assertSame('#externals - Latest RFC Threads', (string) $rss->channel->title);
    $this->assertSame('https://externals.io', (string) $rss->channel->link);
    $this->assertCount(0, $rss->channel->item);
});

test('should build item for each thread', function (): void {
    $threads = [
        new ThreadSummary(
            number: 100,
            subject: '[RFC] My proposal',
            date: CarbonImmutable::parse('2026-02-10 12:34:56'),
            fromName: null,
            fromEmail: null,
            emailCount: 1,
            lastUpdate: CarbonImmutable::parse('2026-02-10 12:34:56'),
            votes: 0,
            isRead: false,
            userVote: null,
        ),
        new ThreadSummary(
            number: 101,
            subject: '[RFC] Another proposal',
            date: CarbonImmutable::parse('2026-02-11 09:00:00'),
            fromName: null,
            fromEmail: null,
            emailCount: 1,
            lastUpdate: CarbonImmutable::parse('2026-02-11 09:00:00'),
            votes: 0,
            isRead: false,
            userVote: null,
        ),
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
});
