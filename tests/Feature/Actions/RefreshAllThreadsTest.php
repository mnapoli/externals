<?php

declare(strict_types=1);

use App\Actions\RefreshAllThreads;
use App\Models\Email;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creates a thread row for every root', function (): void {
    $rootA = Email::factory()->create(['id' => '<root-a>']);
    Email::factory()->replyTo($rootA)->create();
    Email::factory()->create(['id' => '<root-b>']);

    app(RefreshAllThreads::class)->handle();

    $threadA = Thread::find('<root-a>');
    $threadB = Thread::find('<root-b>');
    $this->assertNotNull($threadA);
    $this->assertNotNull($threadB);
    $this->assertSame(2, $threadA->emailCount);
    $this->assertSame(1, $threadB->emailCount);
});

test('ignores non root emails', function (): void {
    $root = Email::factory()->create();
    Email::factory()->replyTo($root)->create();

    app(RefreshAllThreads::class)->handle();

    $this->assertSame(1, Thread::count());
});

test('replaces existing thread rows', function (): void {
    Email::factory()->create(['id' => '<root>', 'number' => 1, 'fetchDate' => '2026-01-01 10:00:00']);
    Thread::factory()->create([
        'emailId' => '<root>',
        'emailNumber' => 1,
        'lastUpdate' => '2020-01-01 00:00:00',
        'emailCount' => 999,
        'votes' => 999,
    ]);

    app(RefreshAllThreads::class)->handle();

    $thread = Thread::find('<root>');
    $this->assertSame(1, $thread->emailCount);
    $this->assertSame(0, $thread->votes);
});
