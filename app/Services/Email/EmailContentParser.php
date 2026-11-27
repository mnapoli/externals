<?php

declare(strict_types=1);

namespace App\Services\Email;

use Illuminate\Support\Facades\Log;
use League\CommonMark\CommonMarkConverter;
use Throwable;
use VStelmakh\UrlHighlight\Highlighter\Anchor;
use VStelmakh\UrlHighlight\Highlighter\CallbackHighlighter;
use VStelmakh\UrlHighlight\Url;
use VStelmakh\UrlHighlight\UrlHighlight;

class EmailContentParser
{
    /**
     * Endings of domains that are also common file extensions, e.g. "opcache.so", "configure.in" or "build.sh".
     * Written without "https://", these are file names, not websites.
     */
    private const array FILE_EXTENSIONS = ['so', 'la', 'in', 'sh', 'py', 'rs', 'pl', 'pm', 'md', 'cc', 'ac', 'am', 'mo', 'zip', 'inc'];

    public function __construct(
        private readonly UrlHighlight $urlHighlight,
        private readonly CommonMarkConverter $markdownParser,
    ) {}

    public function parse(string $content): string
    {
        // Fix for CommonMark issue with <? characters
        // @see https://github.com/mnapoli/externals/issues/15
        $content = str_replace('<?', '&lt;?', $content);

        $content = $this->toMarkdown($content);

        try {
            $content = (string) $this->markdownParser->convert($content);
        } catch (Throwable $e) {
            Log::warning('Unable to parse email content as Markdown: ' . $e->getMessage(), [
                'exception' => $e,
                'text' => $content,
            ]);
            $content = nl2br($content);
        }

        return $this->highlightUrls($content);
    }

    /**
     * Cleans up the plain text of an email (mailing list footer, quote headers…)
     * and returns it as Markdown, without rendering it to HTML.
     */
    public function toMarkdown(string $content): string
    {
        // The decoded text can hold bytes that aren't UTF-8 when an email declares the wrong charset, which MySQL rejects
        $content = mb_scrub($content, 'UTF-8');
        $content = $this->stripMailingListFooter($content);
        $content = mb_rtrim($content, " \t\n\r\0\x0B->");
        $content = $this->stripTrailingUnindentedQuotation($content);
        $content = $this->stripQuoteHeaders($content);
        $content = $this->stripRepeatedLinks($content);

        $content = $this->parsePhpFunctions($content);

        return $this->parsePhpConstants($content);
    }

    /**
     * Mail clients write the words they turned into links followed by the link, e.g. "libphp.so<http://libphp.so>"
     * or "https://php.net<https://php.net>": the text is enough.
     */
    private function stripRepeatedLinks(string $content): string
    {
        $content = preg_replace('#([^\s<>]+)<(?:https?://|mailto:)\1/?>#i', '$1', $content) ?? $content;

        return preg_replace('#((?:https?://|mailto:)[^\s<>]+)<\1/?>#i', '$1', $content) ?? $content;
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

    private function highlightUrls(string $content): string
    {
        $highlighter = new CallbackHighlighter(function (Url $url): string {
            if ($this->isFileName($url)) {
                return $url->full;
            }

            return Anchor::fromUrl($url)
                ->withAttribute('rel', 'nofollow')
                ->withAttribute('target', '_blank')
                ->render();
        });

        return $this->urlHighlight->highlight($content, $highlighter);
    }

    private function isFileName(Url $url): bool
    {
        if ($url->scheme !== null || $url->isEmail() || str_starts_with(mb_strtolower($url->host), 'www.')) {
            return false;
        }
        $extension = mb_strtolower(mb_substr((string) mb_strrchr($url->host, '.'), 1));

        return in_array($extension, self::FILE_EXTENSIONS, true);
    }
}
