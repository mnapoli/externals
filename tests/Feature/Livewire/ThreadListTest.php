<?php

declare(strict_types=1);

use App\Actions\RefreshAllThreads;
use App\Models\Email;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('page count reflects thread root count', function (): void {
    Email::factory()->count(25)->create();
    app(RefreshAllThreads::class)->handle();

    // 25 threads / 20 per page = 2 pages
    Volt::test('thread-list', ['mode' => 'latest'])
        ->assertViewHas('pageCount', 2);
});

test('guest cannot vote', function (): void {
    Email::factory()->create(['number' => 42]);

    Volt::test('thread-list', ['mode' => 'latest'])
        ->call('vote', 42, 1);

    $this->assertDatabaseCount('votes', 0);
});

test('authenticated user can vote', function (): void {
    Email::factory()->create(['number' => 42]);
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('thread-list', ['mode' => 'latest'])
        ->call('vote', 42, 1);

    $this->assertDatabaseHas('votes', [
        'userId' => $user->id,
        'emailNumber' => 42,
        'value' => 1,
    ]);
});

test('clicking the active vote again removes it', function (): void {
    Email::factory()->create(['number' => 42]);
    $user = User::factory()->create();

    $component = Volt::actingAs($user)->test('thread-list', ['mode' => 'latest']);
    $component->call('vote', 42, 1);
    $component->call('vote', 42, 1);

    $this->assertSame(0, (int) Vote::where('userId', $user->id)->where('emailNumber', 42)->sum('value'));
});
