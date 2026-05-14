<?php

declare(strict_types=1);

namespace Feature\Http;

use App\Models\Email;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_404_when_email_does_not_exist(): void
    {
        $response = $this->get('/message/12345');

        $response->assertNotFound();
    }

    public function test_renders_thread_view_for_root_email(): void
    {
        Email::factory()->create(['number' => 100, 'subject' => 'Hello world']);

        $response = $this->get('/message/100');

        $response->assertOk();
        $response->assertViewIs('thread');
        $response->assertViewHas('subject', 'Hello world');
        $response->assertViewHas('threadId', 100);
    }

    public function test_redirects_replies_to_thread_root_with_anchor(): void
    {
        $root = Email::factory()->create(['number' => 100]);
        $reply = Email::factory()->replyTo($root)->create(['number' => 101]);

        $response = $this->get('/message/' . $reply->number);

        $response->assertRedirect("/message/{$root->number}#{$reply->number}");
    }

    public function test_renders_orphan_reply_when_thread_root_missing(): void
    {
        Email::factory()->create([
            'number' => 200,
            'id' => '<reply@example.com>',
            'threadId' => '<missing-root@example.com>',
            'isThreadRoot' => false,
        ]);

        $response = $this->get('/message/200');

        $response->assertOk();
        $response->assertViewIs('thread');
    }

    public function test_marks_email_as_read_for_authenticated_user(): void
    {
        Email::factory()->create(['id' => '<root@example.com>', 'number' => 300]);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/message/300')->assertOk();

        $this->assertDatabaseHas('user_emails_read', [
            'emailId' => '<root@example.com>',
            'userId' => $user->id,
        ]);
    }

    public function test_does_not_mark_as_read_for_guest(): void
    {
        Email::factory()->create(['id' => '<root@example.com>', 'number' => 301]);

        $this->get('/message/301')->assertOk();

        $this->assertDatabaseCount('user_emails_read', 0);
    }
}
