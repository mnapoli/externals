<?php

declare(strict_types=1);

namespace Feature\Actions;

use App\Actions\CastVote;
use App\Models\Email;
use App\Models\Thread;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CastVoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_a_new_vote_and_returns_total(): void
    {
        Email::factory()->create(['number' => 1]);
        $user = User::factory()->create();

        $total = app(CastVote::class)->handle($user->id, 1, 1);

        $this->assertSame(1, $total);
        $this->assertDatabaseHas('votes', ['userId' => $user->id, 'emailNumber' => 1, 'value' => 1]);
    }

    public function test_updates_existing_vote_for_same_user(): void
    {
        Email::factory()->create(['number' => 1]);
        $user = User::factory()->create();

        app(CastVote::class)->handle($user->id, 1, 1);
        $total = app(CastVote::class)->handle($user->id, 1, -1);

        $this->assertSame(-1, $total);
        $this->assertSame(1, Vote::where('userId', $user->id)->where('emailNumber', 1)->count());
    }

    public function test_sums_votes_from_multiple_users(): void
    {
        Email::factory()->create(['number' => 1]);
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        app(CastVote::class)->handle($alice->id, 1, 1);
        $total = app(CastVote::class)->handle($bob->id, 1, 1);

        $this->assertSame(2, $total);
    }

    public function test_refreshes_thread_row(): void
    {
        Email::factory()->create(['id' => '<root>', 'number' => 1]);
        $user = User::factory()->create();

        app(CastVote::class)->handle($user->id, 1, 1);

        $thread = Thread::find('<root>');
        $this->assertNotNull($thread);
        $this->assertSame(1, $thread->votes);
    }
}
