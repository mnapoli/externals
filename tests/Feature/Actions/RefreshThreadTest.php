<?php

declare(strict_types=1);

use App\Actions\RefreshThread;
use App\Models\Email;
use App\Models\Thread;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

uses(RefreshDatabase::class);

test('creates thread row with email count and votes', function (): void {
    $root = Email::factory()->create(['id' => '<root>', 'number' => 1, 'fetchDate' => '2026-01-01 10:00:00']);
    Email::factory()->replyTo($root)->create(['fetchDate' => '2026-01-02 11:00:00']);
    Email::factory()->replyTo($root)->create(['fetchDate' => '2026-01-03 12:00:00']);

    $user = User::factory()->create();
    Vote::factory()->create(['userId' => $user->id, 'emailNumber' => 1, 'value' => 1]);

    app(RefreshThread::class)->handle(1);

    $thread = Thread::find('<root>');
    $this->assertNotNull($thread);
    $this->assertSame(1, $thread->emailNumber);
    $this->assertSame(3, $thread->emailCount);
    $this->assertSame(1, $thread->votes);
    $this->assertSame('2026-01-03 12:00:00', $thread->lastUpdate->format('Y-m-d H:i:s'));
});

test('root with no replies has count one and zero votes', function (): void {
    Email::factory()->create(['id' => '<solo>', 'number' => 10]);

    app(RefreshThread::class)->handle(10);

    $thread = Thread::find('<solo>');
    $this->assertNotNull($thread);
    $this->assertSame(1, $thread->emailCount);
    $this->assertSame(0, $thread->votes);
});

test('replaces existing thread row', function (): void {
    Email::factory()->create(['id' => '<root>', 'number' => 1, 'fetchDate' => '2026-01-01 10:00:00']);
    Thread::factory()->create([
        'emailId' => '<root>',
        'emailNumber' => 1,
        'lastUpdate' => '2020-01-01 00:00:00',
        'emailCount' => 999,
        'votes' => 999,
    ]);

    app(RefreshThread::class)->handle(1);

    $thread = Thread::find('<root>');
    $this->assertSame(1, $thread->emailCount);
    $this->assertSame(0, $thread->votes);
    $this->assertSame('2026-01-01 10:00:00', $thread->lastUpdate->format('Y-m-d H:i:s'));
});

test('only refreshes the target thread', function (): void {
    Email::factory()->create(['id' => '<root-a>', 'number' => 1]);
    Email::factory()->create(['id' => '<root-b>', 'number' => 2]);

    app(RefreshThread::class)->handle(1);

    $this->assertNotNull(Thread::find('<root-a>'));
    $this->assertNull(Thread::find('<root-b>'));
});

test('does nothing when email is not a thread root', function (): void {
    $root = Email::factory()->create(['id' => '<root>', 'number' => 1]);
    Email::factory()->replyTo($root)->create(['number' => 2]);

    app(RefreshThread::class)->handle(2);

    $this->assertNull(Thread::find('<root>'));
});

test('refreshes by email id', function (): void {
    $root = Email::factory()->create(['id' => '<root>', 'number' => 1, 'fetchDate' => '2026-01-01 10:00:00']);
    Email::factory()->replyTo($root)->create(['fetchDate' => '2026-01-02 11:00:00']);

    app(RefreshThread::class)->handle(emailId: '<root>');

    $thread = Thread::find('<root>');
    $this->assertNotNull($thread);
    $this->assertSame(2, $thread->emailCount);
});

test('refreshes only target thread when called by email id', function (): void {
    Email::factory()->create(['id' => '<root-a>', 'number' => 1]);
    Email::factory()->create(['id' => '<root-b>', 'number' => 2]);

    app(RefreshThread::class)->handle(emailId: '<root-a>');

    $this->assertNotNull(Thread::find('<root-a>'));
    $this->assertNull(Thread::find('<root-b>'));
});

test('throws when no argument is provided', function (): void {
    $this->expectException(InvalidArgumentException::class);
    app(RefreshThread::class)->handle();
});

test('throws when both arguments are provided', function (): void {
    $this->expectException(InvalidArgumentException::class);
    app(RefreshThread::class)->handle(1, '<root>');
});
