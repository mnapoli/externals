<?php declare(strict_types=1);

namespace Externals;

use VStelmakh\UrlHighlight\Highlighter\HtmlHighlighter;
use VStelmakh\UrlHighlight\UrlHighlight;

class UrlHighlightFactory
{
    public static function createUrlHighlight(string $defaultScheme, array $attributes): UrlHighlight
    {
        $highlighter = new HtmlHighlighter($defaultScheme, $attributes);
        return new UrlHighlight(null, $highlighter);
    }
}
