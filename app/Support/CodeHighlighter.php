<?php

declare(strict_types=1);

namespace App\Support;

use Tempest\Highlight\Highlighter;

/**
 * Syntax highlighting of the `<pre><code>` blocks of rendered HTML (emails, RFC text).
 */
class CodeHighlighter
{
    /** Languages written in the code fences of emails and wiki pages, mapped to the highlighter's. */
    private const array LANGUAGES = [
        'php' => 'php',
        'php-inline' => 'php',
        'sql' => 'sql',
        'mysql' => 'sql',
        'js' => 'js',
        'javascript' => 'js',
        'ts' => 'ts',
        'typescript' => 'ts',
        'json' => 'json',
        'bash' => 'bash',
        'sh' => 'bash',
        'shell' => 'bash',
        'console' => 'bash',
        'diff' => 'diff',
        'patch' => 'diff',
        'yaml' => 'yaml',
        'yml' => 'yaml',
        'ini' => 'ini',
        'html' => 'html',
        'xml' => 'xml',
        'css' => 'css',
        'python' => 'python',
    ];

    private static ?Highlighter $highlighter = null;

    public function highlight(string $html): string
    {
        if (! str_contains($html, '<pre><code')) {
            return $html;
        }

        return (string) preg_replace_callback(
            '#<pre><code(?: class="language-(?<language>[^"]+)")?>(?<code>.*?)</code></pre>#s',
            function (array $matches): string {
                // URLs in code blocks may have been turned into links
                $code = html_entity_decode(strip_tags($matches['code']), ENT_QUOTES | ENT_HTML5);
                // Blank lines around the code, but not the indentation of its first line
                $code = mb_trim($code, "\r\n");
                $language = $this->language($matches['language'], $code);
                if ($language === null) {
                    return $matches[0];
                }

                self::$highlighter ??= new Highlighter;
                $highlighted = self::$highlighter->parse($code, $language);
                $class = $matches['language'] !== '' ? "language-{$matches['language']} " : '';

                return "<pre><code class=\"{$class}highlighted\">{$highlighted}</code></pre>";
            },
            $html,
        );
    }

    private function language(string $declared, string $code): ?string
    {
        if ($declared !== '') {
            return self::LANGUAGES[mb_strtolower($declared)] ?? null;
        }

        // Code in #internals is PHP, unless proven otherwise. Text indented by the author is not code.
        $looksLikePhp = preg_match('/<\?php|\$[a-zA-Z_]\w*|->|::|\bfunction\b|;\s*$/m', $code) === 1;

        return $looksLikePhp ? 'php' : null;
    }
}
