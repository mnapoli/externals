<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\RefreshThread;
use App\Models\Email;
use App\Models\Thread;
use App\Models\User;
use App\Models\Vote;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefreshThreadTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_thread_row_with_email_count_and_votes(): void
    {
        $this->createEmail('<root>', 1, threadId: '<root>', isThreadRoot: true, fetchDate: '2026-01-01 10:00:00');
        $this->createEmail('<reply-1>', 2, threadId: '<root>', isThreadRoot: false, fetchDate: '2026-01-02 11:00:00');
        $this->createEmail('<reply-2>', 3, threadId: '<root>', isThreadRoot: false, fetchDate: '2026-01-03 12:00:00');

        $user = User::create(['githubId' => 'gh-1', 'name' => 'alice']);
        Vote::create(['userId' => $user->id, 'emailNumber' => 1, 'value' => 1, 'updatedAt' => new DateTimeImmutable]);

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
        $this->createEmail('<solo>', 10, threadId: '<solo>', isThreadRoot: true, fetchDate: '2026-02-01 09:00:00');

        app(RefreshThread::class)->handle(10);

        $thread = Thread::find('<solo>');
        $this->assertNotNull($thread);
        $this->assertSame(1, $thread->emailCount);
        $this->assertSame(0, $thread->votes);
    }

    public function test_replaces_existing_thread_row(): void
    {
        $this->createEmail('<root>', 1, threadId: '<root>', isThreadRoot: true, fetchDate: '2026-01-01 10:00:00');
        Thread::create([
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
        $this->createEmail('<root-a>', 1, threadId: '<root-a>', isThreadRoot: true, fetchDate: '2026-01-01 10:00:00');
        $this->createEmail('<root-b>', 2, threadId: '<root-b>', isThreadRoot: true, fetchDate: '2026-01-02 10:00:00');

        app(RefreshThread::class)->handle(1);

        $this->assertNotNull(Thread::find('<root-a>'));
        $this->assertNull(Thread::find('<root-b>'));
    }

    public function test_does_nothing_when_email_is_not_a_thread_root(): void
    {
        $this->createEmail('<root>', 1, threadId: '<root>', isThreadRoot: true, fetchDate: '2026-01-01 10:00:00');
        $this->createEmail('<reply>', 2, threadId: '<root>', isThreadRoot: false, fetchDate: '2026-01-02 10:00:00');

        app(RefreshThread::class)->handle(2);

        $this->assertNull(Thread::find('<root>'));
    }

    private function createEmail(
        string $id,
        int $number,
        string $threadId,
        bool $isThreadRoot,
        string $fetchDate,
    ): void {
        Email::create([
            'id' => $id,
            'number' => $number,
            'subject' => "Subject $number",
            'content' => '',
            'source' => '',
            'threadId' => $threadId,
            'isThreadRoot' => $isThreadRoot,
            'date' => $fetchDate,
            'fetchDate' => $fetchDate,
            'fromEmail' => 'a@b.c',
            'fromName' => 'Author',
            'inReplyTo' => null,
        ]);
    }
}
