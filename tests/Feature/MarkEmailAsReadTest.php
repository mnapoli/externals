<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\MarkEmailAsRead;
use App\Models\Email;
use App\Models\User;
use App\Models\UserEmailRead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MarkEmailAsReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_read_record(): void
    {
        Carbon::setTestNow('2026-05-14 10:00:00');
        $email = $this->createEmail('<root>', 1);
        $user = User::create(['githubId' => 'gh-1', 'name' => 'alice']);

        app(MarkEmailAsRead::class)->handle($email, $user);

        $row = UserEmailRead::where('userId', $user->id)->where('emailId', $email->id)->first();
        $this->assertNotNull($row);
        $this->assertSame('2026-05-14 10:00:00', $row->lastReadDate->format('Y-m-d H:i:s'));
    }

    public function test_updates_existing_read_record(): void
    {
        $email = $this->createEmail('<root>', 1);
        $user = User::create(['githubId' => 'gh-1', 'name' => 'alice']);

        Carbon::setTestNow('2026-01-01 00:00:00');
        app(MarkEmailAsRead::class)->handle($email, $user);

        Carbon::setTestNow('2026-05-14 10:00:00');
        app(MarkEmailAsRead::class)->handle($email, $user);

        $rows = UserEmailRead::where('userId', $user->id)->where('emailId', $email->id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame('2026-05-14 10:00:00', $rows->first()->lastReadDate->format('Y-m-d H:i:s'));
    }

    private function createEmail(string $id, int $number): Email
    {
        return Email::create([
            'id' => $id,
            'number' => $number,
            'subject' => 'subject',
            'content' => '',
            'source' => '',
            'threadId' => $id,
            'isThreadRoot' => true,
            'date' => '2026-01-01 10:00:00',
            'fetchDate' => '2026-01-01 10:00:00',
            'fromEmail' => 'a@b.c',
            'fromName' => 'Author',
            'inReplyTo' => null,
        ]);
    }
}
