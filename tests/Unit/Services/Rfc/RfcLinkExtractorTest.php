<?php

declare(strict_types=1);

use App\Services\Rfc\RfcLinkExtractor;

test('normalizes wiki page ids into slugs', function (string $pageId, string $slug): void {
    expect(RfcLinkExtractor::normalizeSlug($pageId))->toBe($slug);
})->with([
    ['rfc:pipe-operator-v3', 'pipe-operator-v3'],
    ['rfc/pipe-operator-v3', 'pipe-operator-v3'],
    ['rfc::async_scheduler_abi', 'async_scheduler_abi'],
    ['rfc: serializable_closures ', 'serializable_closures'],
    ['rfc:PHPVCS', 'phpvcs'],
    ['rfc:Some Title', 'some_title'],
    ['rfc:foo:bar', 'foo/bar'],
]);

test('extracts links to RFC pages', function (): void {
    $text = <<<'TEXT'
        <p>Hi internals, here is my RFC: <a href="https://wiki.php.net/rfc/pipe-operator-v3">https://wiki.php.net/rfc/pipe-operator-v3</a>.</p>
        <p>It follows http://wiki.php.net/rfc:pipe-operator-v2#proposal and
        https://wiki.php.net/doku.php?id=rfc:function-composition&rev=1.</p>
        <p>See https://wiki.php.net/rfc and https://wiki.php.net/rfc/howto for the process.</p>
        TEXT;

    expect((new RfcLinkExtractor)->extract($text))->toBe([
        'pipe-operator-v3',
        'pipe-operator-v2',
        'function-composition',
    ]);
});

test('ignores emails that link to many RFCs', function (): void {
    $links = implode("\n", array_map(fn(int $i) => "https://wiki.php.net/rfc/rfc_$i", range(1, 6)));

    $extractor = new RfcLinkExtractor;
    expect($extractor->extract($links))->toHaveCount(6);
    expect($extractor->extractFromThreadRoot($links))->toBe([]);
});
