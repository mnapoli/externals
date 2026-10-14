<?php

declare(strict_types=1);

use App\Models\Email;
use App\Models\EmailReaction;
use App\Models\User;
use App\Services\Email\ReactionQuery;
use App\Support\Reaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('guest is sent to the login instead of reacting', function (): void {
    Email::factory()->create(['number' => 42]);

    Volt::test('email-reactions', ['emailNumber' => 42])
        ->call('toggle', '+1')
        ->assertRedirect(route('login'));

    $this->assertDatabaseCount('email_reactions', 0);
});

test('user can add and remove a reaction', function (): void {
    Email::factory()->create(['number' => 42]);
    $user = User::factory()->create();
    $other = User::factory()->create();
    EmailReaction::create(['userId' => $other->id, 'emailNumber' => 42, 'reaction' => Reaction::Rocket, 'createdAt' => now()]);

    $component = Volt::actingAs($user)->test('email-reactions', ['emailNumber' => 42, 'counts' => [['reaction' => 'rocket', 'count' => 1]]]);

    $component->call('toggle', '+1')
        ->assertSet('counts', [['reaction' => '+1', 'count' => 1], ['reaction' => 'rocket', 'count' => 1]])
        ->assertSet('mine', ['+1']);

    $component->call('toggle', '+1')
        ->assertSet('counts', [['reaction' => 'rocket', 'count' => 1]])
        ->assertSet('mine', []);
    $this->assertDatabaseCount('email_reactions', 1);
});

test('unknown reactions are ignored', function (): void {
    Email::factory()->create(['number' => 42]);

    Volt::actingAs(User::factory()->create())
        ->test('email-reactions', ['emailNumber' => 42])
        ->call('toggle', 'thinking');

    $this->assertDatabaseCount('email_reactions', 0);
});

test('reactions of a thread are counted per email in the order of the picker', function (): void {
    Email::factory()->create(['number' => 1]);
    Email::factory()->create(['number' => 2]);
    [$alice, $bob] = User::factory()->count(2)->create();
    foreach ([[$alice, 1, Reaction::Eyes], [$bob, 1, Reaction::ThumbsUp], [$alice, 1, Reaction::ThumbsUp], [$bob, 2, Reaction::Heart]] as [$user, $number, $reaction]) {
        EmailReaction::create(['userId' => $user->id, 'emailNumber' => $number, 'reaction' => $reaction, 'createdAt' => now()]);
    }

    expect(app(ReactionQuery::class)->forEmails([1, 2, 3], $alice->id))->toBe([
        1 => ['counts' => [['reaction' => '+1', 'count' => 2], ['reaction' => 'eyes', 'count' => 1]], 'mine' => ['eyes', '+1']],
        2 => ['counts' => [['reaction' => 'heart', 'count' => 1]], 'mine' => []],
    ]);
});

test('reactions are shown on the thread page', function (): void {
    Email::factory()->create(['number' => 42]);
    EmailReaction::create(['userId' => User::factory()->create()->id, 'emailNumber' => 42, 'reaction' => Reaction::Hooray, 'createdAt' => now()]);

    $this->get('/message/42')
        ->assertOk()
        ->assertSee('aria-label="Hooray: 1"', false);
});

test('the thumbs down reaction can be toggled', function (): void {
    Email::factory()->create(['number' => 42]);

    Volt::actingAs(User::factory()->create())
        ->test('email-reactions', ['emailNumber' => 42])
        ->call('toggle', '-1')
        ->assertSet('counts', [['reaction' => '-1', 'count' => 1]])
        ->assertSet('mine', ['-1'])
        ->assertSee('aria-label="Thumbs down: 1, including you"', false);
});
