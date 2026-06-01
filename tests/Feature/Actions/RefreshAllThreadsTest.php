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
    }

    public function test_ignores_non_root_emails(): void
    {
        $root = Email::factory()->create();
        Email::factory()->replyTo($root)->create();

        app(RefreshAllThreads::class)->handle();

        $this->assertSame(1, Thread::count());
    }

    public function test_replaces_existing_thread_rows(): void
    {
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
    }
}
