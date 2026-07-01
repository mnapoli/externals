<?php

declare(strict_types=1);

namespace App\Services\Rss;

use App\Support\Email\ThreadSummary;
use DomDocument;
use DomElement;
use RuntimeException;

class RssRfcBuilder
{
    private ?DomDocument $dom = null;

    public function __construct(
        private readonly string $host,
    ) {}

    /**
     * @param  ThreadSummary[]  $threads
     */
    public function build(array $threads): string
    {
        $this->dom = new DomDocument('1.0', 'utf-8');

        $rss = $this->dom->createElement('rss');
        $rss->setAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
        $rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $rss->setAttribute('version', '2.0');
        $this->dom->appendChild($rss);

        $channel = $this->dom->createElement('channel');
        $this->addTextNode('title', '#externals - Latest RFC Threads', $channel);
        $this->addTextNode('link', $this->host, $channel);
        $this->addTextNode('description', 'Latest RFC Threads', $channel);
        $this->addTextNode('pubDate', date('r'), $channel);
        $this->addTextNode('lastBuildDate', date('r'), $channel);
        $rss->appendChild($channel);

        foreach ($threads as $thread) {
            $item = $this->dom->createElement('item');
            $this->addTextNode('title', $thread->subject, $item);
            $this->addTextNode('link', $this->host . '/message/' . $thread->number, $item);
            $this->addTextNode('description', $thread->subject, $item);
            $this->addTextNode('guid', (string) $thread->number, $item);
            $this->addTextNode('pubDate', $thread->date->format('r'), $item);
            $channel->appendChild($item);
        }

        $xml = $this->dom->saveXML();
        if ($xml === false) {
            throw new RuntimeException('Failed to generate RSS XML');
        }

        return $xml;
    }

    private function addTextNode(string $name, string $value, DomElement $parent): void
    {
        $element = $this->dom->createElement($name);
        $element->appendChild($this->dom->createTextNode($value));
        $parent->appendChild($element);
    }
}
