<?php

declare(strict_types=1);

use App\Models\Email;
use App\Models\Rfc;
use App\Services\Rfc\RfcStatus;
use App\Services\Rfc\RfcSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Sleep::fake();
});

/**
 * Fakes wiki.php.net and records the RFC pages that were downloaded.
 *
 * @param  array<string, string>  $changes  Date of the last change, by slug
 */
function fakeWiki(string $index, array $changes = [], array &$downloaded = []): void
{
    // Replace the fakes of the previous calls
    Http::swap(new Factory(app('events')));
    Http::fake(function (Request $request) use ($index, $changes, &$downloaded) {
        $url = $request->url();
        if (str_starts_with($url, 'https://wiki.php.net/rfc?')) {
            return Http::response($index);
        }
        if (str_starts_with($url, 'https://wiki.php.net/feed.php')) {
            $items = '';
            foreach ($changes as $slug => $date) {
                $items .= "<item rdf:about=\"https://wiki.php.net/rfc/$slug?rev=1\"><dc:date>$date</dc:date><link>https://wiki.php.net/rfc/$slug?rev=1</link></item>";
            }

            return Http::response('<?xml version="1.0" encoding="UTF-8"?><rdf:RDF xmlns="http://purl.org/rss/1.0/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/"><channel></channel>' . $items . '</rdf:RDF>');
        }
        if (preg_match('#^https://wiki\.php\.net/rfc/([^?]+)\?do=export_xhtmlbody#', $url, $matches) && $matches[1] !== 'missing') {
            $downloaded[] = $matches[1];

            return Http::response(<<<HTML
                <h1>PHP RFC: Title of {$matches[1]}</h1>
                <div class="level1"><ul>
                <li class="level1"><div class="li"> Version: 1.0</div></li>
                <li class="level1"><div class="li"> Author: Alice</div></li>
                </ul></div>
                <h2>Introduction</h2>
                <p>Text of {$matches[1]}. Discussion: <a href="https://externals.io/message/101">https://externals.io/message/101</a></p>
                <table class="doodle__results">
                <tr class="title_row"><th class="title_caption">Accept?</th></tr>
                <tr class="fields_row"><th class="fields_caption">Real name</th><th class="fields_data">Yes</th><th class="fields_data">No</th></tr>
                <tr class="data_row"><td class="data_caption">alice</td><td class="data_data"><img title="2026/01/02 10:00"></td><td class="data_data"></td></tr>
                <tr class="title_row"><td class="title_caption">This poll will close on 2026-01-10 00:00:00 UTC.</td></tr>
                </table>
                HTML);
        }

        return Http::response('Not found', 404);
    });
}

const RFC_INDEX = <<<'WIKI'
    ===== In voting phase =====
      * [[rfc:voted|Voted RFC]]
    ===== Under Discussion =====
      * [[rfc:discussed|Discussed RFC]]
    WIKI;

test('imports the RFCs of the wiki', function (): void {
    $root = Email::factory()->create(['number' => 100]);
    Email::factory()->replyTo($root)->create(['number' => 101]);
    fakeWiki(RFC_INDEX);

    $result = app(RfcSynchronizer::class)->synchronize();

    expect($result)->toBe(['rfcs' => 2, 'fetched' => 2, 'failed' => 0, 'remaining' => 0]);
    $rfc = Rfc::where('slug', 'discussed')->firstOrFail();
    expect($rfc->title)->toBe('Discussed RFC');
    expect($rfc->status)->toBe(RfcStatus::Discussion);
    expect($rfc->version)->toBe('1.0');
    expect($rfc->authors)->toBe('Alice');
    expect($rfc->content)->toStartWith("## Introduction\n\nText of discussed.");
    expect($rfc->fetchedAt)->not->toBeNull();

    $poll = $rfc->polls()->sole();
    expect($poll->question)->toBe('Accept?');
    expect($poll->isClosed)->toBeFalse();
    expect($poll->closesAt)->toBe('2026-01-10 00:00:00 UTC');
    expect($poll->choices)->toEqual([['label' => 'Yes', 'count' => 1], ['label' => 'No', 'count' => 0]]);
    expect($poll->voters)->toEqual([['name' => 'alice', 'choices' => ['Yes'], 'votedAt' => '2026-01-02 10:00']]);

    // The page links to a reply: the RFC is linked to its thread
    expect(DB::table('rfc_threads')->where('rfcSlug', 'discussed')->pluck('threadNumber')->all())->toBe([100]);
});

test('only downloads new, changed and voted RFCs', function (): void {
    fakeWiki(RFC_INDEX);
    app(RfcSynchronizer::class)->synchronize();

    $downloaded = [];
    fakeWiki(RFC_INDEX, [], $downloaded);
    app(RfcSynchronizer::class)->synchronize();
    // Votes don't change the page: RFCs in voting are always downloaded
    expect($downloaded)->toBe(['voted']);

    $downloaded = [];
    fakeWiki(RFC_INDEX, ['discussed' => now()->addMinute()->toAtomString()], $downloaded);
    app(RfcSynchronizer::class)->synchronize();
    expect($downloaded)->toBe(['voted', 'discussed']);
});

test('downloads RFCs whose status changed', function (): void {
    fakeWiki(RFC_INDEX);
    app(RfcSynchronizer::class)->synchronize();

    $downloaded = [];
    fakeWiki("===== Accepted =====\n  * [[rfc:voted|Voted RFC]]\n===== Under Discussion =====\n  * [[rfc:discussed|Discussed RFC]]", [], $downloaded);
    app(RfcSynchronizer::class)->synchronize();

    expect($downloaded)->toBe(['voted']);
    expect(Rfc::where('slug', 'voted')->value('status'))->toBe(RfcStatus::Accepted);
});

test('limits the number of pages downloaded per run', function (): void {
    fakeWiki(RFC_INDEX);

    $result = app(RfcSynchronizer::class)->synchronize(maxPages: 1);

    expect($result)->toBe(['rfcs' => 2, 'fetched' => 1, 'failed' => 0, 'remaining' => 1]);
    expect(Rfc::whereNull('fetchedAt')->count())->toBe(1);
});

test('keeps RFCs removed from the index, unlisted', function (): void {
    fakeWiki(RFC_INDEX);
    app(RfcSynchronizer::class)->synchronize();

    fakeWiki("===== Under Discussion =====\n  * [[rfc:discussed|Discussed RFC]]");
    app(RfcSynchronizer::class)->synchronize();

    expect(Rfc::where('slug', 'voted')->value('isListed'))->toBeFalse();
    expect(Rfc::where('slug', 'discussed')->value('isListed'))->toBeTrue();
});

test('a page that cannot be downloaded does not stop the synchronization', function (): void {
    fakeWiki("===== Under Discussion =====\n  * [[rfc:discussed|Discussed RFC]]\n  * [[rfc:missing|Missing]]");

    $result = app(RfcSynchronizer::class)->synchronize();

    expect($result['fetched'])->toBe(1);
    expect($result['failed'])->toBe(1);
});
