<?php declare(strict_types=1);

namespace Externals\Email;

use League\CommonMark\CommonMarkConverter;
use Psr\Log\LoggerInterface;
use Throwable;
use VStelmakh\UrlHighlight\UrlHighlight;

class EmailContentParser
{
    private UrlHighlight $urlHighlight;
    private CommonMarkConverter $markdownParser;
    private LoggerInterface $logger;

    public function __construct(
        UrlHighlight $urlHighlight,
        CommonMarkConverter $markdownParser,
        LoggerInterface $logger
    ) {
        $this->urlHighlight = $urlHighlight;
        $this->markdownParser = $markdownParser;
        $this->logger = $logger;
    }

    public function parse(string $content): string
    {
        // Fix for CommonMark (the standard) issue with <? characters
        // @see https://github.com/mnapoli/externals/issues/15
        $content = str_replace('<?', '&lt;?', $content);

        $content = $this->stripMailingListFooter($content);
        $content = rtrim($content, " \t\n\r\0\x0B->");
        $content = $this->stripTrailingUnindentedQuotation($content);
        $content = $this->stripQuoteHeaders($content);

        // Auto-transform PHP functions to inline code
        $content = $this->parsePhpFunctions($content);
        // Auto-transform PHP constants to inline code
        $content = $this->parsePhpConstants($content);

        try {
            $content = $this->markdownParser->convertToHtml($content);
        } catch (Throwable $e) {
            $this->logger->warning('Unable to parse email content as Markdown: ' . $e->getMessage(), [
                'exception' => $e,
                'text' => $content,
            ]);
            // Fallback to basic formatting:
            $content = nl2br($content);
        }

        $content = $this->urlHighlight->highlightUrls($content);

        return $content;
    }

    private function parsePhpFunctions(string $content): string
    {
        return preg_replace_callback('/\s([a-zA-Z0-9_]+)\(\)/', function ($matches): string {
            $function = $matches[1];
            if (function_exists($function)) {
                return " `$function()`";
            }
            return $matches[0];
        }, $content);
    }

    private function parsePhpConstants(string $content): string
    {
        return preg_replace_callback('/\s([A-Z_]+)\s/', function ($matches): string {
            $name = $matches[1];
            if (defined($name)) {
                return " `$name` ";
            }
            return $matches[0];
        }, $content);
    }

    private function stripMailingListFooter(string $content): string
    {
        $footer = [
            'PHP Internals - PHP Runtime Development Mailing List',
            'To unsubscribe, visit: http://www.php.net/unsub.php',
        ];
        return str_replace($footer, '', $content);
    }

    private function stripTrailingUnindentedQuotation(string $content): string
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

    private function stripQuoteHeaders(string $content): string
    {
        return preg_replace('/^([> ]*)On .+, .+ wrote:\r?$/m', '$1', $content);
    }
}
