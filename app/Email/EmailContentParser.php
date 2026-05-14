<?php

declare(strict_types=1);

namespace App\Email;

use League\CommonMark\CommonMarkConverter;
use Psr\Log\LoggerInterface;
use Throwable;
use VStelmakh\UrlHighlight\UrlHighlight;

class EmailContentParser
{
    public function __construct(
        private readonly UrlHighlight $urlHighlight,
        private readonly CommonMarkConverter $markdownParser,
        private readonly LoggerInterface $logger,
    ) {}

    public function parse(string $content): string
    {
        // Fix for CommonMark issue with <? characters
        // @see https://github.com/mnapoli/externals/issues/15
        $content = str_replace('<?', '&lt;?', $content);

        $content = $this->stripMailingListFooter($content);
        $content = rtrim($content, " \t\n\r\0\x0B->");
        $content = $this->stripTrailingUnindentedQuotation($content);
        $content = $this->stripQuoteHeaders($content);

        $content = $this->parsePhpFunctions($content);
        $content = $this->parsePhpConstants($content);

        try {
            $content = (string) $this->markdownParser->convert($content);
        } catch (Throwable $e) {
            $this->logger->warning('Unable to parse email content as Markdown: '.$e->getMessage(), [
                'exception' => $e,
                'text' => $content,
            ]);
            $content = nl2br($content);
        }

        return $this->urlHighlight->highlightUrls($content);
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
