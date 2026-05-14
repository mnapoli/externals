<?php

declare(strict_types=1);

namespace App\Services\Rss;

use App\Models\Email;
use DomDocument;
use DomElement;

class RssBuilder
{
    private ?DomDocument $dom = null;

    public function __construct(private readonly string $host) {}

    /**
     * @param  iterable<Email>  $emails
     */
    public function build(iterable $emails): string
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
        $this->addTextNode('description', "Opening PHP's #internals to the outside", $channel);
        $this->addTextNode('pubDate', date('r'), $channel);
        $this->addTextNode('lastBuildDate', date('r'), $channel);
        $rss->appendChild($channel);

        foreach ($emails as $email) {
            $item = $this->dom->createElement('item');
            $this->addTextNode('title', $email->subject, $item);
            $this->addTextNode('link', $this->host.'/message/'.$email->number, $item);
            $this->addTextNode('description', $email->content, $item);
            $this->addTextNode('guid', $email->id, $item);
            $this->addTextNode('pubDate', $email->date->format('r'), $item);
            $channel->appendChild($item);
        }

        return $this->dom->saveXML();
    }

    private function addTextNode(string $name, string $value, DomElement $parent): void
    {
        $element = $this->dom->createElement($name);
        $element->appendChild($this->dom->createTextNode($value));
        $parent->appendChild($element);
    }
}
