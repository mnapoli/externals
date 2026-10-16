<?php

declare(strict_types=1);

use App\Models\Rfc;
use App\Services\Rfc\ThreadRfcs;
use Illuminate\Support\Str;

function rfcTitled(string $title): Rfc
{
    return (new Rfc)->forceFill(['slug' => Str::slug($title), 'title' => $title]);
}

test('the only RFC of a thread is its RFC when the subject is about it', function (string $subject, string $title, string $slug): void {
    $rfc = (new Rfc)->forceFill(['slug' => $slug, 'title' => $title]);

    $threadRfcs = ThreadRfcs::classify($subject, [['rfc' => $rfc, 'fromWiki' => false]]);

    expect($threadRfcs->primary)->toBe($rfc);
    expect($threadRfcs->mentions)->toBe([]);
})->with([
    'phrased differently' => ['BLAKE3 hash redux', 'Add support for the BLAKE3 cryptographic hash function', 'blake3'],
    'plural' => ['[RFC] Deprecate/Remove MD5 checksums', 'MD5 Release Checksum', 'release_checksum'],
    'only the slug matches' => ['[VOTE] Catching Multiple Exception Types', 'Multi catch', 'multiple-catch'],
    'nothing but tags' => ['[RFC] [VOTE]', 'Deprecations for PHP 8.6', 'deprecations_php_8_6'],
]);

test('a thread that only cites its only RFC has no RFC', function (): void {
    $rfc = rfcTitled('Voting Process');

    $threadRfcs = ThreadRfcs::classify('Voting access', [['rfc' => $rfc, 'fromWiki' => false]]);

    expect($threadRfcs->primary)->toBeNull();
    expect($threadRfcs->mentions)->toBe([$rfc]);
});

test('the RFC whose page links to the thread is its RFC', function (): void {
    $own = rfcTitled('Left-to-right assignment operator');
    $older = rfcTitled('Pipe Assignment Operator');

    $threadRfcs = ThreadRfcs::classify('[RFC] Something else entirely', [
        ['rfc' => $older, 'fromWiki' => false],
        ['rfc' => $own, 'fromWiki' => true],
    ]);

    expect($threadRfcs->primary)->toBe($own);
    expect($threadRfcs->mentions)->toBe([$older]);
});

test('without a link from the wiki, the RFC matching the subject is its RFC', function (): void {
    $own = rfcTitled('Pipe to return');
    $older = rfcTitled('Pipe Assignment Operator');

    $threadRfcs = ThreadRfcs::classify('[RFC] [Discussion] Pipe to return', [
        ['rfc' => $older, 'fromWiki' => false],
        ['rfc' => $own, 'fromWiki' => false],
    ]);

    expect($threadRfcs->primary)->toBe($own);
    expect($threadRfcs->mentions)->toBe([$older]);
});

test('a thread that only mentions RFCs has no RFC', function (): void {
    $rfcs = [rfcTitled('Explicit closure interfaces'), rfcTitled('Callable prototypes'), rfcTitled('Structural Typing for Closures')];

    $threadRfcs = ThreadRfcs::classify('[Discussion] Revisiting typed closures', array_map(fn(Rfc $rfc) => ['rfc' => $rfc, 'fromWiki' => false], $rfcs));

    expect($threadRfcs->primary)->toBeNull();
    expect($threadRfcs->mentions)->toBe($rfcs);
});

test('two RFCs matching the subject as well are both mentions', function (): void {
    $rfcs = [rfcTitled('Typed closures'), rfcTitled('Closures typed')];

    $threadRfcs = ThreadRfcs::classify('Typed closures', array_map(fn(Rfc $rfc) => ['rfc' => $rfc, 'fromWiki' => false], $rfcs));

    expect($threadRfcs->primary)->toBeNull();
});
