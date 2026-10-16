<?php

declare(strict_types=1);

use App\Support\ShortLinks;

test('links get short labels', function (): void {
    $link = fn(string $url, string $label) => '<a class="text-accent-strong underline decoration-accent-line underline-offset-2 hover:decoration-accent-strong" href="' . $url . '">' . $label . '</a>';

    expect(ShortLinks::html('https://github.com/php/php-src/pull/17118'))->toBe($link('https://github.com/php/php-src/pull/17118', 'php/php-src#17118'))
        ->and(ShortLinks::html('https://github.com/php/php-src/commit/1c09c0c8323342aaaecbe444b3afa45efbd72ced.'))
        ->toBe($link('https://github.com/php/php-src/commit/1c09c0c8323342aaaecbe444b3afa45efbd72ced', 'php/php-src@1c09c0c') . '.')
        ->and(ShortLinks::html('<b>PoC</b> at https://www.example.com/pear?a=1&b=2'))
        ->toBe('&lt;b&gt;PoC&lt;/b&gt; at ' . $link('https://www.example.com/pear?a=1&amp;b=2', 'example.com/pear?a=1&amp;b=2'));
});
