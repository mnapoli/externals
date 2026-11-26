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
        $html = '';
        foreach (preg_split('#(https?://[^\s<>"]+[^\s<>".,;:)])#', $text, flags: PREG_SPLIT_DELIM_CAPTURE) ?: [] as $i => $part) {
            // Odd parts are the URLs captured by the split
            $html .= $i % 2 === 0 ? e($part) : sprintf(
                '<a class="text-accent-strong underline decoration-accent-line underline-offset-2 hover:decoration-accent-strong" href="%s">%s</a>',
                e($part),
                e(self::label($part)),
            );
        }

        return $html;
    }

    private static function label(string $url): string
    {
        if (preg_match('#^https?://github\.com/([^/]+/[^/]+)/(?:pull|issues)/(\d+)#', $url, $github)) {
            return "{$github[1]}#{$github[2]}";
        }
        if (preg_match('#^https?://github\.com/([^/]+/[^/]+)/commit/([0-9a-f]{7})#', $url, $github)) {
            return "{$github[1]}@{$github[2]}";
        }

        return (string) preg_replace('#^https?://(www\.)?#', '', $url);
    }
}
