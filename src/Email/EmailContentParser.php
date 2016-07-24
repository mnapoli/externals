<?php
declare(strict_types = 1);

namespace Externals\Email;

use League\CommonMark\DocParser;
use League\CommonMark\HtmlRenderer;
use Misd\Linkify\Linkify;
use Psr\Log\LoggerInterface;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class EmailContentParser
{
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

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Linkify $linkify,
        DocParser $markdownParser,
        HtmlRenderer $htmlRenderer,
        LoggerInterface $logger
    ) {
        $this->linkify = $linkify;
        $this->markdownParser = $markdownParser;
        $this->htmlRenderer = $htmlRenderer;
        $this->logger = $logger;
    }

    public function parse(string $content) : string
    {
        $content = $this->stripMailingListFooter($content);
        $content = rtrim($content, " \t\n\r\0\x0B->");
        $content = $this->stripTrailingUnindentedQuotation($content);
        $content = $this->stripQuoteHeaders($content);

        // Auto-transform PHP functions to inline code
        $content = $this->parsePhpFunctions($content);
        // Auto-transform PHP constants to inline code
        $content = $this->parsePhpConstants($content);

        try {
            $content = $this->htmlRenderer->renderBlock($this->markdownParser->parse($content));
        } catch (\Exception $e) {
            $this->logger->warning('Unable to parse email content as Markdown: ' . $e->getMessage(), [
                'exception' => $e,
                'text' => $content,
            ]);
            // Fallback to basic formatting:
            $content = nl2br($content);
        }

        $content = $this->linkify->process($content, [
            'attr' => [
                'rel' => 'nofollow',
                'target' => '_blank',
            ],
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

    private function stripMailingListFooter(string $content)
    {
        $footer = [
            'PHP Internals - PHP Runtime Development Mailing List',
            'To unsubscribe, visit: http://www.php.net/unsub.php',
        ];
        return str_replace($footer, '', $content);
    }

    private function stripTrailingUnindentedQuotation(string $content) : string
    {
        return preg_replace('/
                # At least 2 line breaks
                \R\R
                # A line of "---" or "___" (at least 2 of them)
                [-_]{2,}\R
                # Optional extra line break
                \R?
                From: .+$
            /sx', '', $content);
    }

    private function stripQuoteHeaders(string $content) : string
    {
        return preg_replace('/^([> ]*)On .+, .+ wrote:\r?$/m', '$1', $content);
    }
}
