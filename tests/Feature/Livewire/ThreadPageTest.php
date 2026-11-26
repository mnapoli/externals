<?php

declare(strict_types=1);

use App\Actions\RefreshAllThreads;
use App\Models\Email;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('replies deeper than the indentation limit say who they answer', function (): void {
    $parent = Email::factory()->create(['number' => 1, 'fromName' => 'Root author']);
    foreach (range(2, 6) as $number) {
        $parent = Email::factory()->replyTo($parent)->create([
            'number' => $number,
            'fromName' => "Author $number",
            'date' => now()->addMinutes($number),
        ]);
    }

    $html = $this->get('/message/1')->assertOk()->getContent();

    // Depth 4 and 5 are past the limit of 3 levels of indentation
    expect($html)->toContain('title="In reply to Author 4"')
        ->toContain('title="In reply to Author 5"')
        ->not->toContain('title="In reply to Author 3"');
});

test('the thread page shows the unread messages wherever they are in the tree', function (): void {
    $user = User::factory()->create();
    $root = Email::factory()->create(['number' => 1, 'fetchDate' => '2026-01-01 10:00:00']);
    $old = Email::factory()->replyTo($root)->create(['number' => 2, 'fetchDate' => '2026-01-01 11:00:00']);
    Email::factory()->replyTo($root)->create(['number' => 3, 'fetchDate' => '2026-01-01 12:00:00']);
    // A new reply to an old message, in the middle of the thread
    Email::factory()->replyTo($old)->create(['number' => 4, 'fetchDate' => '2026-01-03 10:00:00']);
    DB::table('user_emails_read')->insert(['emailId' => $root->id, 'userId' => $user->id, 'lastReadDate' => '2026-01-02 10:00:00']);

    $html = $this->actingAs($user)->get('/message/1')->assertOk()->getContent();

    expect(mb_substr_count($html, 'class="group/msg unread"'))->toBe(1);
    expect($html)->toContain('Jump to first unread');
});

test('user can vote on the thread page', function (): void {
    Email::factory()->create(['number' => 42]);
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('thread-vote', ['number' => 42])
        ->call('vote', 1)
        ->assertSet('votes', 1)
        ->assertSet('userVote', 1)
        ->call('vote', 1)
        ->assertSet('votes', 0)
        ->assertSet('userVote', 0);
});

test('the thread list counts the messages received since the last visit', function (): void {
    $user = User::factory()->create();
    $root = Email::factory()->create(['number' => 1, 'fetchDate' => '2026-01-01 10:00:00']);
    Email::factory()->replyTo($root)->create(['number' => 2, 'fetchDate' => '2026-01-03 10:00:00']);
    Email::factory()->replyTo($root)->create(['number' => 3, 'fetchDate' => '2026-01-03 11:00:00']);
    app(RefreshAllThreads::class)->handle();
    DB::table('user_emails_read')->insert(['emailId' => $root->id, 'userId' => $user->id, 'lastReadDate' => '2026-01-02 10:00:00']);

    Volt::actingAs($user)
        ->test('thread-list', ['mode' => 'latest'])
        ->assertSee('2 new');
});
