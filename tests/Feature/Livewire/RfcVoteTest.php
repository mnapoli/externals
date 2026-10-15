<?php

declare(strict_types=1);

use App\Models\Rfc;
use App\Models\RfcVote;
use App\Models\User;
use App\Services\Rfc\RfcList;
use App\Services\Rfc\RfcStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('guest cannot vote on an RFC', function (): void {
    $rfc = Rfc::factory()->create();

    Volt::test('rfc-vote', ['rfcId' => $rfc->id])
        ->call('vote', 1);

    $this->assertDatabaseCount('rfc_votes', 0);
});

test('user can upvote and downvote an RFC', function (): void {
    $rfc = Rfc::factory()->create();
    $user = User::factory()->create();
    RfcVote::create(['userId' => User::factory()->create()->id, 'rfcId' => $rfc->id, 'value' => 1, 'updatedAt' => now()]);

    $component = Volt::actingAs($user)->test('rfc-vote', ['rfcId' => $rfc->id, 'upvotes' => 1]);

    $component->call('vote', 1)
        ->assertSet('upvotes', 2)
        ->assertSet('downvotes', 0)
        ->assertSet('userVote', 1);

    $component->call('vote', -1)
        ->assertSet('upvotes', 1)
        ->assertSet('downvotes', 1)
        ->assertSet('userVote', -1);
    $this->assertDatabaseHas('rfc_votes', ['userId' => $user->id, 'rfcId' => $rfc->id, 'value' => -1]);
});

test('clicking the active vote again removes it', function (): void {
    $rfc = Rfc::factory()->create();
    $user = User::factory()->create();

    Volt::actingAs($user)->test('rfc-vote', ['rfcId' => $rfc->id])
        ->call('vote', 1)
        ->call('vote', 1)
        ->assertSet('upvotes', 0)
        ->assertSet('userVote', 0);

    $this->assertDatabaseCount('rfc_votes', 0);
});

test('archived RFCs are closed to community votes', function (): void {
    $rfc = Rfc::factory()->create(['status' => RfcStatus::Implemented]);
    $user = User::factory()->create();

    Volt::actingAs($user)->test('rfc-vote', ['rfcId' => $rfc->id, 'canVote' => false])
        ->call('vote', 1);
    // Even if the client claims otherwise
    Volt::actingAs($user)->test('rfc-vote', ['rfcId' => $rfc->id])
        ->call('vote', 1);

    $this->assertDatabaseCount('rfc_votes', 0);
});

test('RFCs under discussion are sorted by community score', function (): void {
    $first = Rfc::factory()->create(['title' => 'First in the wiki', 'indexPosition' => 1]);
    $popular = Rfc::factory()->create(['title' => 'Popular', 'indexPosition' => 2]);
    $unpopular = Rfc::factory()->create(['title' => 'Unpopular', 'indexPosition' => 0]);
    $last = Rfc::factory()->create(['title' => 'Last in the wiki', 'indexPosition' => 3]);
    foreach ([[$popular, 1], [$popular, 1], [$unpopular, -1]] as [$rfc, $value]) {
        RfcVote::create(['userId' => User::factory()->create()->id, 'rfcId' => $rfc->id, 'value' => $value, 'updatedAt' => now()]);
    }

    $sections = app(RfcList::class)->sections();

    expect(array_map(fn(Rfc $rfc) => $rfc->title, $sections[0]['rfcs']))
        ->toBe(['Popular', 'First in the wiki', 'Last in the wiki', 'Unpopular']);
    expect($sections[0]['rfcs'][0]->upvotes)->toBe(2);
});

test('the RFC pages show the community vote of active RFCs only', function (): void {
    Rfc::factory()->create(['slug' => 'active', 'title' => 'Active RFC']);
    Rfc::factory()->create(['slug' => 'archived', 'title' => 'Archived RFC', 'status' => RfcStatus::Declined]);

    $this->get('/rfc/active')->assertOk()->assertSee('Community vote')->assertSee('In favor');
    $this->get('/rfc/archived')->assertOk()->assertDontSee('Community vote');
});

test('the RFC page keeps the community opinion of archived RFCs, without letting people vote', function (): void {
    $rfc = Rfc::factory()->create(['slug' => 'archived', 'title' => 'Archived RFC', 'status' => RfcStatus::Declined]);
    RfcVote::create(['userId' => User::factory()->create()->id, 'rfcId' => $rfc->id, 'value' => 1, 'updatedAt' => now()]);

    $this->get('/rfc/archived')->assertOk()
        ->assertSee('Community vote')
        ->assertSee('1 in favor')
        ->assertDontSee('wire:click="vote(1)"', false);
});
