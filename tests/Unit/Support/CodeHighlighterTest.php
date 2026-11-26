<?php

declare(strict_types=1);

use App\Support\CodeHighlighter;

test('PHP code blocks are highlighted', function (): void {
    $html = (new CodeHighlighter)->highlight("<p>Example:</p>\n<pre><code>\$foo = new Foo(); // comment\n</code></pre>");

    expect($html)->toContain('<pre><code class="highlighted">')
        ->toContain('<span class="hl-keyword">new</span>')
        ->toContain('<span class="hl-comment">// comment</span>');
});

test('the language of the code fence is used', function (): void {
    $html = (new CodeHighlighter)->highlight('<pre><code class="language-sql">SELECT * FROM emails</code></pre>');

    expect($html)->toContain('<pre><code class="language-sql highlighted"><span class="hl-keyword">SELECT</span>');
});

test('code is escaped once highlighted', function (): void {
    $html = (new CodeHighlighter)->highlight('<pre><code>$a = &quot;&lt;script&gt;&quot;;</code></pre>');

    expect($html)->not->toContain('<script>')->toContain('&lt;script&gt;');
});

test('indented text that does not look like PHP is left alone', function (): void {
    $html = '<pre><code>Benchmark results:
  before   1.2s
  after    0.8s
</code></pre>';

    expect((new CodeHighlighter)->highlight($html))->toBe($html);
});

test('languages the highlighter does not know are left alone', function (): void {
    $html = '<pre><code class="language-c">int main(void) { return 0; }</code></pre>';

    expect((new CodeHighlighter)->highlight($html))->toBe($html);
});

test('blank lines around the code are removed, not its indentation', function (): void {
    $html = (new CodeHighlighter)->highlight("<pre><code>\n    \$a = 1;\n\n</code></pre>");

    expect($html)->toStartWith('<pre><code class="highlighted">    <span class="hl-variable">$a</span>')
        ->toEndWith(';</code></pre>');
});
