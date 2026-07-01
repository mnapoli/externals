<?php

declare(strict_types=1);

use App\Actions\RefreshAllThreads;
use App\Models\Email;
use App\Models\User;
use App\Models\Vote;
use App\Services\Email\ThreadQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('find latest threads orders by last update desc', function (): void {
    Email::factory()->create(['number' => 1, 'fetchDate' => '2026-01-01 10:00:00']);
    Email::factory()->create(['number' => 2, 'fetchDate' => '2026-03-01 10:00:00']);
    Email::factory()->create(['number' => 3, 'fetchDate' => '2026-02-01 10:00:00']);
    app(RefreshAllThreads::class)->handle();

    $threads = (new ThreadQuery)->findLatestThreads(1, null);

    $this->assertCount(3, $threads);
    $this->assertSame(2, $threads[0]->number);
    $this->assertSame(3, $threads[1]->number);
    $this->assertSame(1, $threads[2]->number);
});

test('find latest rfc threads only includes subjects containing rfc', function (): void {
    Email::factory()->create(['subject' => '[RFC] My idea']);
    Email::factory()->create(['subject' => 'Just a discussion']);
    app(RefreshAllThreads::class)->handle();

    $threads = (new ThreadQuery)->findLatestRfcThreads();

    $this->assertCount(1, $threads);
    $this->assertSame('[RFC] My idea', $threads[0]->subject);
});

test('find latest threads paginates results', function (): void {
    // 21 threads so the second page contains exactly one
    Email::factory()->count(21)->create();
    app(RefreshAllThreads::class)->handle();

    $page1 = (new ThreadQuery)->findLatestThreads(1, null);
    $page2 = (new ThreadQuery)->findLatestThreads(2, null);

    $this->assertCount(20, $page1);
    $this->assertCount(1, $page2);
});

test('find latest threads returns user vote when user is given', function (): void {
    Email::factory()->create(['number' => 1]);
    $user = User::factory()->create();
    Vote::factory()->create(['userId' => $user->id, 'emailNumber' => 1, 'value' => 1]);
    app(RefreshAllThreads::class)->handle();

    $threads = (new ThreadQuery)->findLatestThreads(1, $user);

    $this->assertSame(1, $threads[0]->userVote);
});

test('get thread view builds tree of replies', function (): void {
    $root = Email::factory()->create(['id' => '<root>', 'date' => '2026-01-01 10:00:00']);
    $reply1 = Email::factory()->replyTo($root)->create(['id' => '<reply-1>', 'date' => '2026-01-02 10:00:00']);
    Email::factory()->replyTo($reply1)->create(['id' => '<reply-1-1>', 'date' => '2026-01-03 10:00:00']);
    Email::factory()->replyTo($root)->create(['id' => '<reply-2>', 'date' => '2026-01-04 10:00:00']);

    $rootItems = (new ThreadQuery)->getThreadView($root);

    $this->assertCount(1, $rootItems);
    $this->assertSame('<root>', $rootItems[0]->email->id);
    $this->assertCount(2, $rootItems[0]->replies);
    $this->assertSame('<reply-1>', $rootItems[0]->replies[0]->email->id);
    $this->assertCount(1, $rootItems[0]->replies[0]->replies);
    $this->assertSame('<reply-1-1>', $rootItems[0]->replies[0]->replies[0]->email->id);
    $this->assertSame('<reply-2>', $rootItems[0]->replies[1]->email->id);
    $this->assertCount(0, $rootItems[0]->replies[1]->replies);
});

test('get thread view promotes replies with unknown parent to root', function (): void {
    // The "real" thread root is missing — orphaned replies should still surface.
    $orphan = Email::factory()->create([
        'id' => '<orphan>',
        'threadId' => '<missing>',
        'isThreadRoot' => false,
        'inReplyTo' => '<missing>',
    ]);

    $rootItems = (new ThreadQuery)->getThreadView($orphan);

    $this->assertCount(1, $rootItems);
    $this->assertSame('<orphan>', $rootItems[0]->email->id);
});
