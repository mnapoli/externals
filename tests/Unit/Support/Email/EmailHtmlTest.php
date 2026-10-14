<?php

declare(strict_types=1);

use App\Support\Email\EmailHtml;

test('lines wrapped by the mail client are joined', function (): void {
    $html = "<p>a <code>PREG_THROW_ON_ERROR</code> flag you pass to any <code>preg_*()</code> call, so a PCRE <br>\nerror throws an exception.</p>";

    expect(EmailHtml::unwrapLines($html))
        ->toBe('<p>a <code>PREG_THROW_ON_ERROR</code> flag you pass to any <code>preg_*()</code> call, so a PCRE error throws an exception.</p>');
});

test('short lines keep their line break', function (): void {
    $html = "<p>Best regards <br>\nTim Düsterhus</p>";

    expect(EmailHtml::unwrapLines($html))->toBe($html);
});

test('long quotes are folded', function (): void {
    $html = "<blockquote>\n<p>one <br>\ntwo <br>\nthree <br>\nfour</p>\n</blockquote>\n<p>My answer</p>";

    $presented = EmailHtml::foldLongQuotes($html);

    expect($presented)->toContain('<details class="quote"><summary>Quoted text · 4 lines</summary><blockquote>');
    expect($presented)->toContain('<p>My answer</p>');
});

test('short quotes stay visible', function (): void {
    $html = "<blockquote>\n<p>Should we do this?</p>\n</blockquote>\n<p>Yes</p>";

    expect(EmailHtml::foldLongQuotes($html))->toBe($html);
});

test('non ASCII text survives quote folding', function (): void {
    $html = "<blockquote>\n<p>a <br>\nb <br>\nc <br>\nd</p>\n</blockquote>\n<p>Düsterhus – “quotes” 👍</p>";

    expect(EmailHtml::foldLongQuotes($html))->toContain('<p>Düsterhus – “quotes” 👍</p>');
});
