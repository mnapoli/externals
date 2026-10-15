<?php

declare(strict_types=1);

use App\Actions\RefreshAllThreads;
use App\Models\Email;
use App\Models\Rfc;
use App\Models\RfcPoll;
use App\Models\Thread;
use App\Services\Rfc\RfcStatus;
use App\Services\Rfc\RfcThreads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

/**
 * An RFC with a vote and a thread that discusses it.
 */
function rfcWithThread(): Rfc
{
    $rfc = Rfc::factory()->create([
        'slug' => 'pipe-operator-v3',
        'title' => 'Pipe operator',
        'status' => RfcStatus::Implemented,
        'indexSubsection' => 'PHP 8.5',
        'phpVersion' => '8.5',
        'indexPosition' => 5,
        'authors' => 'Larry Garfield',
        'implementation' => 'https://github.com/php/php-src/pull/17118',
        'content' => "## Introduction\n\nSee [the other RFC](https://wiki.php.net/rfc/enumerations) and [an unknown one](https://wiki.php.net/rfc/unknown).",
    ]);
    Rfc::factory()->create(['slug' => 'enumerations', 'title' => 'Enumerations', 'status' => RfcStatus::Voting, 'indexPosition' => 0]);
    RfcPoll::create([
        'rfcId' => $rfc->id,
        'position' => 0,
        'question' => 'Add the pipe operator?',
        'isClosed' => true,
        'choices' => [['label' => 'Yes', 'count' => 33], ['label' => 'No', 'count' => 7]],
        'voters' => [['name' => 'alcaeus', 'choices' => ['Yes'], 'votedAt' => '2025-05-14 06:37']],
    ]);

    $root = Email::factory()->create([
        'number' => 100,
        'subject' => '[RFC] Pipe Operator (again)',
        'date' => '2025-02-07 10:00:00',
        'content' => '<p>Here is my RFC: https://wiki.php.net/rfc/pipe-operator-v3</p>',
    ]);
    Thread::factory()->create(['emailId' => $root->id, 'emailNumber' => 100, 'emailCount' => 12, 'lastUpdate' => '2025-03-01 10:00:00']);
    app(RfcThreads::class)->linkThreadRoot($root);

    return $rfc;
}

test('lists the RFCs by status', function (): void {
    rfcWithThread();
    Rfc::factory()->create(['slug' => 'unlisted', 'title' => 'Unlisted RFC', 'isListed' => false]);

    $response = $this->get('/rfc');

    $response->assertOk();
    // Sections of the same status are grouped in the navigation, e.g. the PHP versions under "Implemented"
    $response->assertSeeInOrder(['In voting', 'Implemented', 'href="#section-implemented-php-85"', 'PHP 8.5'], false);
    $response->assertSeeInOrder(['Voting', 'Enumerations', 'Implemented', 'PHP 8.5', 'Pipe operator', 'Larry Garfield', '1 thread']);
    $response->assertSee('href="/rfc/pipe-operator-v3"', false);
    $response->assertDontSee('Unlisted RFC');
});

test('shows the first RFCs of each section, and the others on demand', function (): void {
    foreach (range(1, 12) as $i) {
        Rfc::factory()->create(['title' => "Declined RFC $i", 'status' => RfcStatus::Declined, 'indexPosition' => $i]);
    }

    $component = Volt::test('pages.rfcs')
        ->assertSee('Declined RFC 10')
        ->assertDontSee('Declined RFC 11')
        ->assertSee('Show 2 more');

    $component->call('expand', 'Declined')
        ->assertSee('Declined RFC 12')
        ->assertDontSee('Show 2 more');
});

test('filters the RFCs by title or slug', function (): void {
    rfcWithThread();
    foreach (range(1, 12) as $i) {
        Rfc::factory()->create(['title' => "Declined RFC $i", 'status' => RfcStatus::Declined, 'indexPosition' => 10 + $i]);
    }

    Volt::test('pages.rfcs')
        ->set('q', 'PIPE-operator')
        ->assertSee('Pipe operator')
        ->assertDontSee('Enumerations')
        // All the matches, without "Show more"
        ->set('q', 'declined rfc')
        ->assertSee('Declined RFC 12')
        ->assertDontSee('Show 2 more')
        ->set('q', 'nothing like this')
        ->assertSee('No RFC matches');
});

test('shows an RFC with its votes, threads and text', function (): void {
    rfcWithThread();

    $response = $this->get('/rfc/pipe-operator-v3');

    $response->assertOk();
    $response->assertSee('Pipe operator');
    $response->assertSee('Implemented · PHP 8.5');
    $response->assertSee('Larry Garfield');
    $response->assertSeeInOrder(['Add the pipe operator?', 'Yes', '33', 'No', '7', 'alcaeus']);
    $response->assertSee('href="/message/100"', false);
    $response->assertSee('[RFC] Pipe Operator (again)');
    $response->assertSee('<h2 id="rfc-introduction">Introduction</h2>', false);
    // Links to imported RFCs stay on externals.io
    $response->assertSee('href="' . url('/rfc/enumerations') . '"', false);
    $response->assertSee('href="https://wiki.php.net/rfc/unknown"', false);
    // The original is the wiki page
    $response->assertSee('<link rel="canonical" href="https://wiki.php.net/rfc/pipe-operator-v3">', false);
});

test('an unknown RFC is not found', function (): void {
    $this->get('/rfc/unknown')->assertNotFound();
    $this->get('/rfc/unknown.md')->assertNotFound();
    $this->get('/api/rfcs/unknown')->assertNotFound()->assertJsonStructure(['message']);
});

test('RFC urls are normalized', function (): void {
    rfcWithThread();

    $this->get('/rfc/Pipe-Operator-V3')->assertRedirect('/rfc/pipe-operator-v3');
});

test('renders an RFC as markdown', function (): void {
    rfcWithThread();

    $response = $this->get('/rfc/pipe-operator-v3.md');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
    $response->assertHeader('Link', '<https://wiki.php.net/rfc/pipe-operator-v3>; rel="canonical"');
    expect($response->getContent())
        ->toStartWith("# RFC: Pipe operator\n")
        ->toContain('- Status: Implemented (PHP 8.5)')
        ->toContain('- Authors: Larry Garfield')
        ->toContain('- Original: https://wiki.php.net/rfc/pipe-operator-v3')
        ->toContain("### Add the pipe operator? (closed)\n\nYes: 33 · No: 7\n\nVoters: alcaeus (Yes)")
        ->toContain('- [\[RFC\] Pipe Operator (again)](' . url('/message/100.md') . ') — started on 2025-02-07, 12 messages')
        ->toContain("## Introduction\n\nSee [the other RFC](" . url('/rfc/enumerations') . ')');
});

test('serves the markdown version of an RFC to agents that prefer it', function (): void {
    rfcWithThread();

    $response = $this->get('/rfc/pipe-operator-v3', ['Accept' => 'text/markdown, text/html;q=0.9']);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
    expect($response->getContent())->toStartWith("# RFC: Pipe operator\n");
    $this->get('/rfc/pipe-operator-v3')->assertHeader('Vary', 'Accept');
});

test('renders the list of RFCs as markdown', function (): void {
    rfcWithThread();

    $response = $this->get('/rfc.md');

    $response->assertOk();
    expect($response->getContent())
        ->toStartWith("# PHP RFCs\n")
        ->toContain("## In voting\n\n- [Enumerations](" . url('/rfc/enumerations.md') . ')')
        ->toContain("## Implemented — PHP 8.5\n\n- [Pipe operator](" . url('/rfc/pipe-operator-v3.md') . ')');
    expect($this->get('/rfc', ['Accept' => 'text/markdown'])->getContent())->toStartWith("# PHP RFCs\n");
});

test('lists the RFCs in the api', function (): void {
    rfcWithThread();

    $response = $this->getJson('/api/rfcs');

    $response->assertOk();
    $response->assertJsonPath('data.0.slug', 'enumerations');
    $response->assertJsonPath('data.1.slug', 'pipe-operator-v3');
    $response->assertJsonPath('data.1.status', 'implemented');
    $response->assertJsonPath('data.1.phpVersion', '8.5');
    $response->assertJsonPath('data.1.threadCount', 1);
    $response->assertJsonPath('data.1.markdownUrl', url('/rfc/pipe-operator-v3.md'));

    $this->getJson('/api/rfcs?status=voting')->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'enumerations');
    $this->getJson('/api/rfcs?status=nope')->assertUnprocessable();
});

test('returns an RFC in the api', function (): void {
    rfcWithThread();

    $response = $this->getJson('/api/rfcs/pipe-operator-v3');

    $response->assertOk();
    $response->assertJsonPath('data.title', 'Pipe operator');
    $response->assertJsonPath('data.wikiUrl', 'https://wiki.php.net/rfc/pipe-operator-v3');
    $response->assertJsonPath('data.polls.0.choices', [['label' => 'Yes', 'count' => 33], ['label' => 'No', 'count' => 7]]);
    $response->assertJsonPath('data.threads.0.number', 100);
    $response->assertJsonPath('data.threads.0.messageCount', 12);
    expect($response->json('data.content'))->toStartWith('## Introduction');
});

test('threads show the RFCs they discuss', function (): void {
    rfcWithThread();

    $this->get('/message/100')
        ->assertSee('href="/rfc/pipe-operator-v3"', false)
        ->assertSee('Implemented · PHP 8.5')
        ->assertSee('Yes: 33 · No: 7');

    expect($this->get('/message/100.md')->getContent())
        ->toContain('- RFC: Pipe operator (Implemented (PHP 8.5)) — ' . url('/rfc/pipe-operator-v3.md'));

    $this->getJson('/api/threads/100')
        ->assertJsonPath('data.rfcs.0.slug', 'pipe-operator-v3')
        ->assertJsonPath('data.rfcs.0.apiUrl', url('/api/rfcs/pipe-operator-v3'));
});

test('replies that link to an RFC do not link their thread', function (): void {
    $root = Email::factory()->create(['number' => 100, 'content' => 'Hello']);
    $reply = Email::factory()->replyTo($root)->create(['number' => 101, 'content' => 'See https://wiki.php.net/rfc/foo']);

    app(RfcThreads::class)->linkThreadRoot($reply);

    expect(DB::table('rfc_threads')->count())->toBe(0);
});

test('links existing threads to RFCs', function (): void {
    Email::factory()->create(['number' => 100, 'content' => 'https://wiki.php.net/rfc/foo and https://wiki.php.net/rfc/bar']);
    Email::factory()->create(['number' => 101, 'content' => 'No RFC here']);

    $this->artisan('externals:link-rfc-threads')->assertSuccessful();

    expect(DB::table('rfc_threads')->orderBy('rfcSlug')->get(['rfcSlug', 'threadNumber', 'source'])->map(fn($row) => (array) $row)->all())->toBe([
        ['rfcSlug' => 'bar', 'threadNumber' => 100, 'source' => 'email'],
        ['rfcSlug' => 'foo', 'threadNumber' => 100, 'source' => 'email'],
    ]);
});

test('the list of RFCs shows the avatar or the initials of the first author', function (): void {
    Rfc::factory()->create(['authors' => 'Larry Garfield (Larry@GarfieldTech.com), Ilija Tovilo']);
    Rfc::factory()->create(['authors' => 'Someone without email']);

    $response = $this->get('/rfc');

    $response->assertOk();
    expect(mb_substr_count($response->getContent(), 'https://www.gravatar.com/avatar/'))->toBe(1);
    $response->assertSee('https://www.gravatar.com/avatar/' . hash('sha256', 'larry@garfieldtech.com') . '?s=48&amp;d=blank', false);
    $response->assertSee('LG');
    $response->assertSee('SE');
});

test('RFCs only mentioned by a thread are not shown as its RFC', function (): void {
    Rfc::factory()->create(['slug' => 'pipe-to-return', 'title' => 'Pipe to return', 'status' => RfcStatus::Discussion]);
    Rfc::factory()->create(['slug' => 'pipe-assignment', 'title' => 'Pipe Assignment Operator', 'status' => RfcStatus::Declined]);
    Email::factory()->create(['number' => 300, 'subject' => '[RFC] [Discussion] Pipe to return']);
    app(RefreshAllThreads::class)->handle();
    DB::table('rfc_threads')->insert([
        ['rfcSlug' => 'pipe-to-return', 'threadNumber' => 300, 'source' => RfcThreads::SOURCE_EMAIL],
        ['rfcSlug' => 'pipe-assignment', 'threadNumber' => 300, 'source' => RfcThreads::SOURCE_EMAIL],
    ]);

    $list = $this->get('/')->getContent();
    expect($list)->toContain('RFC · Discussion')->not->toContain('RFC · Declined');

    $this->get('/message/300')
        ->assertSeeInOrder(['Pipe to return', 'Also mentions', 'Pipe Assignment Operator']);

    $this->get('/rfc/pipe-to-return')->assertSeeInOrder(['id="discussions"', 'href="/message/300"'], false)->assertDontSee('Mentioned in');
    $this->get('/rfc/pipe-assignment')->assertSeeInOrder(['Mentioned in', '[RFC] [Discussion] Pipe to return']);
});
