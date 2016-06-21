<?php
declare(strict_types = 1);

namespace Externals\Email;

use League\CommonMark\DocParser;
use League\CommonMark\HtmlRenderer;
use Misd\Linkify\Linkify;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class EmailContentParser
{
    const FOOTER = [
        'PHP Internals - PHP Runtime Development Mailing List',
        'To unsubscribe, visit: http://www.php.net/unsub.php',
    ];
    const QUOTE = '>';

    /**
     * @var Linkify
     */
    private $linkify;

    /**
     * @var DocParser
     */
    private $markdownParser;

    /**
     * @var HtmlRenderer
     */
    private $htmlRenderer;

    public function __construct(Linkify $linkify, DocParser $markdownParser, HtmlRenderer $htmlRenderer)
    {
        $this->linkify = $linkify;
        $this->markdownParser = $markdownParser;
        $this->htmlRenderer = $htmlRenderer;
    }

    public function parse(string $content) : string
    {
        $content = str_replace(self::FOOTER, '', $content);
        $content = trim($content, " \t\n\r\0\x0B->");

        $content = $this->htmlRenderer->renderBlock($this->markdownParser->parse($content));

        $content = $this->linkify->process($content, [
            'attr' => ['rel' => 'nofollow'],
        ]);

        return $content;
    }
}
