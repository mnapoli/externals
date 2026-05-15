<?php

declare(strict_types=1);

namespace Feature\Actions;

use App\Actions\RefreshThread;
use App\Models\Email;
use App\Models\Thread;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class RefreshThreadTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_thread_row_with_email_count_and_votes(): void
    {
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
    }

    public function test_root_with_no_replies_has_count_one_and_zero_votes(): void
    {
        Email::factory()->create(['id' => '<solo>', 'number' => 10]);

        app(RefreshThread::class)->handle(10);

        $thread = Thread::find('<solo>');
        $this->assertNotNull($thread);
        $this->assertSame(1, $thread->emailCount);
        $this->assertSame(0, $thread->votes);
    }

    public function test_replaces_existing_thread_row(): void
    {
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
    }

    public function test_only_refreshes_the_target_thread(): void
    {
        Email::factory()->create(['id' => '<root-a>', 'number' => 1]);
        Email::factory()->create(['id' => '<root-b>', 'number' => 2]);

        app(RefreshThread::class)->handle(1);

        $this->assertNotNull(Thread::find('<root-a>'));
        $this->assertNull(Thread::find('<root-b>'));
    }

    public function test_does_nothing_when_email_is_not_a_thread_root(): void
    {
        $root = Email::factory()->create(['id' => '<root>', 'number' => 1]);
        Email::factory()->replyTo($root)->create(['number' => 2]);

        app(RefreshThread::class)->handle(2);

        $this->assertNull(Thread::find('<root>'));
    }

    public function test_refreshes_by_email_id(): void
    {
        $root = Email::factory()->create(['id' => '<root>', 'number' => 1, 'fetchDate' => '2026-01-01 10:00:00']);
        Email::factory()->replyTo($root)->create(['fetchDate' => '2026-01-02 11:00:00']);

        app(RefreshThread::class)->handle(emailId: '<root>');

        $thread = Thread::find('<root>');
        $this->assertNotNull($thread);
        $this->assertSame(2, $thread->emailCount);
    }

    public function test_refreshes_only_target_thread_when_called_by_email_id(): void
    {
        Email::factory()->create(['id' => '<root-a>', 'number' => 1]);
        Email::factory()->create(['id' => '<root-b>', 'number' => 2]);

        app(RefreshThread::class)->handle(emailId: '<root-a>');

        $this->assertNotNull(Thread::find('<root-a>'));
        $this->assertNull(Thread::find('<root-b>'));
    }

    public function test_throws_when_no_argument_is_provided(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(RefreshThread::class)->handle();
    }

    public function test_throws_when_both_arguments_are_provided(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(RefreshThread::class)->handle(1, '<root>');
    }
}
