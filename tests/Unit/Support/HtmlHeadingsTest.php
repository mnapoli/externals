<?php

declare(strict_types=1);

use App\Support\HtmlHeadings;

test('gives an id to the sections of the text', function (): void {
    $result = HtmlHeadings::anchor('<h2>Introduction</h2><p>Text</p><h3>Use <code>|&gt;</code> &amp; more</h3><h2>Introduction</h2><h4>Deep</h4>', 'rfc-');

    expect($result['html'])->toBe('<h2 id="rfc-introduction">Introduction</h2><p>Text</p><h3 id="rfc-use-more">Use <code>|&gt;</code> &amp; more</h3><h2 id="rfc-introduction-2">Introduction</h2><h4>Deep</h4>');
    expect($result['headings'])->toBe([
        ['level' => 2, 'id' => 'rfc-introduction', 'text' => 'Introduction'],
        ['level' => 3, 'id' => 'rfc-use-more', 'text' => 'Use |> & more'],
        ['level' => 2, 'id' => 'rfc-introduction-2', 'text' => 'Introduction'],
    ]);
});
