<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Rss;

use App\Models\Email;
use App\Services\Rss\RssBuilder;
use DateTimeImmutable;
use SimpleXMLElement;
use Tests\TestCase;

class RssBuilderTest extends TestCase
{
    public function test_should_build_valid_rss_with_no_items(): void
    {
        $xml = (new RssBuilder('https://externals.io'))->build([]);

        $rss = new SimpleXMLElement($xml);
        $this->assertSame('rss', $rss->getName());
        $this->assertSame('2.0', (string) $rss['version']);
        $this->assertSame('#externals', (string) $rss->channel->title);
        $this->assertSame('https://externals.io', (string) $rss->channel->link);
        $this->assertCount(0, $rss->channel->item);
    }

    public function test_should_build_item_for_each_email(): void
    {
        $email = new Email([
            'id' => '<msg-1>',
            'number' => 42,
            'subject' => 'Hello world',
            'content' => '<p>Body</p>',
            'date' => new DateTimeImmutable('2026-01-15 10:00:00'),
        ]);

        $xml = (new RssBuilder('https://externals.io'))->build([$email]);

        $rss = new SimpleXMLElement($xml);
        $this->assertCount(1, $rss->channel->item);
        $item = $rss->channel->item[0];
        $this->assertSame('Hello world', (string) $item->title);
        $this->assertSame('https://externals.io/message/42', (string) $item->link);
        $this->assertSame('<p>Body</p>', (string) $item->description);
        $this->assertSame('<msg-1>', (string) $item->guid);
        $this->assertSame((new DateTimeImmutable('2026-01-15 10:00:00'))->format('r'), (string) $item->pubDate);
    }

    public function test_should_escape_special_characters_in_subject(): void
    {
        $email = new Email([
            'id' => '<msg-1>',
            'number' => 1,
            'subject' => 'Subject with <tags> & "quotes"',
            'content' => '',
            'date' => new DateTimeImmutable('2026-01-01 00:00:00'),
        ]);

        $xml = (new RssBuilder('https://externals.io'))->build([$email]);

        // Round-trip through SimpleXMLElement: special chars must survive intact.
        $rss = new SimpleXMLElement($xml);
        $this->assertSame('Subject with <tags> & "quotes"', (string) $rss->channel->item[0]->title);
    }
}
