<?php

declare(strict_types=1);

namespace Feature\Actions;

use App\Actions\RefreshAllThreads;
use App\Models\Email;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefreshAllThreadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_thread_row_for_every_root(): void
    {
        $this->createEmail('<root-a>', 1, threadId: '<root-a>', isThreadRoot: true, fetchDate: '2026-01-01 10:00:00');
        $this->createEmail('<reply-a>', 2, threadId: '<root-a>', isThreadRoot: false, fetchDate: '2026-01-02 10:00:00');
        $this->createEmail('<root-b>', 3, threadId: '<root-b>', isThreadRoot: true, fetchDate: '2026-01-03 10:00:00');

        app(RefreshAllThreads::class)->handle();

        $threadA = Thread::find('<root-a>');
        $threadB = Thread::find('<root-b>');
        $this->assertNotNull($threadA);
        $this->assertNotNull($threadB);
        $this->assertSame(2, $threadA->emailCount);
        $this->assertSame(1, $threadB->emailCount);
    }

    public function test_ignores_non_root_emails(): void
    {
        $this->createEmail('<root>', 1, threadId: '<root>', isThreadRoot: true, fetchDate: '2026-01-01 10:00:00');
        $this->createEmail('<reply>', 2, threadId: '<root>', isThreadRoot: false, fetchDate: '2026-01-02 10:00:00');

        app(RefreshAllThreads::class)->handle();

        $this->assertSame(1, Thread::count());
    }

    public function test_replaces_existing_thread_rows(): void
    {
        $this->createEmail('<root>', 1, threadId: '<root>', isThreadRoot: true, fetchDate: '2026-01-01 10:00:00');
        Thread::create([
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
