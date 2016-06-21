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

        // Auto-transform PHP functions to inline code
        $content = $this->parsePhpFunctions($content);
        // Auto-transform PHP constants to inline code
        $content = $this->parsePhpConstants($content);

        $content = $this->htmlRenderer->renderBlock($this->markdownParser->parse($content));

        $content = $this->linkify->process($content, [
            'attr' => ['rel' => 'nofollow'],
        ]);

        return $content;
    }

    private function parsePhpFunctions(string $content) : string
    {
        return preg_replace_callback('/\s([a-zA-Z0-9_]+)\(\)/', function ($matches) : string {
            $function = $matches[1];
            if (function_exists($function)) {
                return " `$function()`";
            }
            return $matches[0];
        }, $content);
    }

    private function parsePhpConstants(string $content) : string
    {
        return preg_replace_callback('/\s([A-Z_]+)\s/', function ($matches) : string {
            $name = $matches[1];
            if (defined($name)) {
                return " `$name` ";
            }
            return $matches[0];
        }, $content);
    }
}
