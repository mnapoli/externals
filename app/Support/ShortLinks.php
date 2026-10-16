<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Links in a line of text, with short labels: "php/php-src#17118" rather than the whole URL.
 */
final class ShortLinks
{
    /**
     * @return string HTML, the rest of the text escaped
     */
    public static function html(string $text): string
    {
        return (string) preg_replace_callback('#https?://[^\s<>"]+[^\s<>".,;:)]#', fn(array $matches) => sprintf(
            '<a class="text-accent-strong underline decoration-accent-line underline-offset-2 hover:decoration-accent-strong" href="%s">%s</a>',
            $matches[0],
            self::label(html_entity_decode($matches[0], ENT_QUOTES | ENT_HTML5)),
        ), e($text));
    }

    private static function label(string $url): string
    {
        if (preg_match('#^https?://github\.com/([^/]+/[^/]+)/(?:pull|issues)/(\d+)#', $url, $github)) {
            return e("{$github[1]}#{$github[2]}");
        }
        if (preg_match('#^https?://github\.com/([^/]+/[^/]+)/commit/([0-9a-f]{7})#', $url, $github)) {
            return e("{$github[1]}@{$github[2]}");
        }

        return e((string) preg_replace('#^https?://(www\.)?#', '', $url));
    }
}
