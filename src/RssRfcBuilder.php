<?php declare(strict_types=1);

namespace Externals;

use DomDocument;
use DomElement;
use Psr\Http\Message\ServerRequestInterface;

class RssRfcBuilder
{
    /** @var string $host The base url to use for links. */
    private $host;

    /** @var DomDocument|null $dom The current xml document being built. */
    private $dom;

    public function __construct(ServerRequestInterface $request)
    {
        $this->host = (string) $request->getUri()->withPath('');
    }

    /**
     * @param array[] $threads
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
            $this->addTextNode('title', $thread['subject'], $item);
            $this->addTextNode('link', $this->host . '/message/' . $thread['number'], $item);
            $this->addTextNode('description', $thread['subject'], $item);
            $this->addTextNode('guid', $thread['number'], $item);
            $this->addTextNode('pubDate', (new \DateTime($thread['date']))->format('r'), $item);
            $channel->appendChild($item);
        }

        return $this->dom->saveXML();
    }

    private function addTextNode(string $name, string $value, DomElement $parent): void
    {
        $element = $this->dom->createElement($name);

        $node = $this->dom->createTextNode($value);
        $element->appendChild($node);

        $parent->appendChild($element);
    }
}
