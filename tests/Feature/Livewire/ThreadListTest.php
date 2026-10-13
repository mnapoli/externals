<?php

declare(strict_types=1);

use App\Actions\RefreshAllThreads;
use App\Models\Email;
use App\Models\Rfc;
use App\Models\User;
use App\Models\Vote;
use App\Services\Rfc\RfcStatus;
use App\Services\Rfc\RfcThreads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

test('top mode lists recently updated threads with positive votes', function (): void {
    Email::factory()->create([
        'number' => 42,
        'subject' => 'Voted recent thread',
        'fetchDate' => now()->subDays(3),
    ]);
    Vote::factory()->create(['userId' => User::factory()->create()->id, 'emailNumber' => 42, 'value' => 1]);
    app(RefreshAllThreads::class)->handle();

    Volt::test('thread-list', ['mode' => 'top'])
        ->assertSee('Voted recent thread');
});

test('top mode excludes threads without positive votes', function (): void {
    Email::factory()->create([
        'number' => 42,
        'subject' => 'Unvoted recent thread',
        'fetchDate' => now()->subDays(3),
    ]);
    app(RefreshAllThreads::class)->handle();

    Volt::test('thread-list', ['mode' => 'top'])
        ->assertDontSee('Unvoted recent thread');
});

test('top mode excludes voted threads older than a month', function (): void {
    Email::factory()->create([
        'number' => 42,
        'subject' => 'Voted stale thread',
        'fetchDate' => now()->subMonths(2),
    ]);
    Vote::factory()->create(['userId' => User::factory()->create()->id, 'emailNumber' => 42, 'value' => 1]);
    app(RefreshAllThreads::class)->handle();

    Volt::test('thread-list', ['mode' => 'top'])
        ->assertDontSee('Voted stale thread');
});

test('threads link to the RFCs they discuss', function (): void {
    Email::factory()->create(['number' => 42, 'subject' => '[RFC] Pipe operator']);
    Email::factory()->create(['number' => 43, 'subject' => 'Unrelated']);
    app(RefreshAllThreads::class)->handle();
    Rfc::factory()->create(['slug' => 'pipe-operator-v3', 'title' => 'Pipe operator', 'status' => RfcStatus::Voting]);
    // Linked from both the email and the wiki: a single badge
    DB::table('rfc_threads')->insert([
        ['rfcSlug' => 'pipe-operator-v3', 'threadNumber' => 42, 'source' => RfcThreads::SOURCE_EMAIL],
        ['rfcSlug' => 'pipe-operator-v3', 'threadNumber' => 42, 'source' => RfcThreads::SOURCE_WIKI],
    ]);

    $html = Volt::test('thread-list', ['mode' => 'latest'])->html();

    expect(mb_substr_count($html, 'href="/rfc/pipe-operator-v3"'))->toBe(1);
    expect($html)->toContain('RFC · In voting');
});
