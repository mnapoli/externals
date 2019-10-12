<?php declare(strict_types=1);

namespace Externals;

use DomDocument;
use DomElement;
use Externals\Email\Email;

class RssBuilder
{
    /** @var string $host The base url to use for links. */
    private $host;

    /** @var DomDocument|null $dom The current xml document being built. */
    private $dom;

    public function __construct(string $host)
    {
        $this->host = $host;
    }

    /**
     * @param Email[] $emails
     */
    public function build(array $emails): string
    {
        $this->dom = new DomDocument('1.0', 'utf-8');

        $rss = $this->dom->createElement('rss');
        $rss->setAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
        $rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $rss->setAttribute('version', '2.0');
        $this->dom->appendChild($rss);

        $channel = $this->dom->createElement('channel');
        $this->addTextNode('title', '#externals', $channel);
        $this->addTextNode('link', $this->host, $channel);
        $this->addTextNode('description', 'Opening PHP\'s #internals to the outside', $channel);
        $this->addTextNode('pubDate', date('r'), $channel);
        $this->addTextNode('lastBuildDate', date('r'), $channel);
        $rss->appendChild($channel);

        foreach ($emails as $email) {
            $item = $this->dom->createElement('item');
            $this->addTextNode('title', $email->getSubject(), $item);
            $this->addTextNode('link', $this->host . '/message/' . $email->getNumber(), $item);
            $this->addTextNode('description', $email->getContent(), $item);
            $this->addTextNode('guid', $email->getId(), $item);
            $this->addTextNode('pubDate', $email->getDate()->format('r'), $item);
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
