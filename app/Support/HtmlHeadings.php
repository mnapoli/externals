<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Table of contents of a long HTML text.
 */
final class HtmlHeadings
{
    /**
     * Gives an id to the h2 and h3 of the HTML, as rendered by CommonMark (headings without attributes).
     *
     * @param  string  $prefix  Keeps the ids apart from the others of the page
     * @return array{html: string, headings: list<array{level: int, id: string, text: string}>}
     */
    public static function anchor(string $html, string $prefix): array
    {
        $headings = [];
        $used = [];
        $html = (string) preg_replace_callback('#<h([23])>(.*?)</h\1>#s', function (array $matches) use ($prefix, &$headings, &$used): string {
            $text = mb_trim(html_entity_decode(strip_tags($matches[2]), ENT_QUOTES | ENT_HTML5));
            $id = $prefix . (Str::slug($text) ?: 'section');
            $used[$id] = ($used[$id] ?? 0) + 1;
            if ($used[$id] > 1) {
                $id .= '-' . $used[$id];
            }
            $headings[] = ['level' => (int) $matches[1], 'id' => $id, 'text' => $text];

            return "<h{$matches[1]} id=\"{$id}\">{$matches[2]}</h{$matches[1]}>";
        }, $html);

        return ['html' => $html, 'headings' => $headings];
    }
}
