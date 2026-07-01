<?php

declare(strict_types=1);

use App\Actions\CastVote;
use App\Models\Email;
use App\Models\Thread;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('records a new vote and returns total', function (): void {
    Email::factory()->create(['number' => 1]);
    $user = User::factory()->create();

    $total = app(CastVote::class)->handle($user->id, 1, 1);

    $this->assertSame(1, $total);
    $this->assertDatabaseHas('votes', ['userId' => $user->id, 'emailNumber' => 1, 'value' => 1]);
});

test('updates existing vote for same user', function (): void {
    Email::factory()->create(['number' => 1]);
    $user = User::factory()->create();

    app(CastVote::class)->handle($user->id, 1, 1);
    $total = app(CastVote::class)->handle($user->id, 1, -1);

    $this->assertSame(-1, $total);
    $this->assertSame(1, Vote::where('userId', $user->id)->where('emailNumber', 1)->count());
});

test('sums votes from multiple users', function (): void {
    Email::factory()->create(['number' => 1]);
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    app(CastVote::class)->handle($alice->id, 1, 1);
    $total = app(CastVote::class)->handle($bob->id, 1, 1);

    $this->assertSame(2, $total);
});

test('refreshes thread row', function (): void {
    Email::factory()->create(['id' => '<root>', 'number' => 1]);
    $user = User::factory()->create();

    app(CastVote::class)->handle($user->id, 1, 1);

    $thread = Thread::find('<root>');
    $this->assertNotNull($thread);
    $this->assertSame(1, $thread->votes);
});
