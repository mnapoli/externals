<?php

declare(strict_types=1);

use App\Models\Email;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('returns 404 when email does not exist', function (): void {
    $response = $this->get('/message/12345');

    $response->assertNotFound();
});

test('renders thread view for root email', function (): void {
    Email::factory()->create(['number' => 100, 'subject' => 'Hello world']);

    $response = $this->get('/message/100');

    $response->assertOk();
    $response->assertSee('Hello world');
});

test('redirects replies to thread root with anchor', function (): void {
    $root = Email::factory()->create(['number' => 100]);
    $reply = Email::factory()->replyTo($root)->create(['number' => 101]);

    $response = $this->get('/message/' . $reply->number);

    $response->assertRedirect("/message/{$root->number}#{$reply->number}");
});

test('renders orphan reply when thread root missing', function (): void {
    Email::factory()->create([
        'number' => 200,
        'id' => '<reply@example.com>',
        'threadId' => '<missing-root@example.com>',
        'isThreadRoot' => false,
    ]);

    $response = $this->get('/message/200');

    $response->assertOk();
});

test('marks email as read for authenticated user', function (): void {
    Email::factory()->create(['id' => '<root@example.com>', 'number' => 300]);
    $user = User::factory()->create();

    $this->actingAs($user)->get('/message/300')->assertOk();

    $this->assertDatabaseHas('user_emails_read', [
        'emailId' => '<root@example.com>',
        'userId' => $user->id,
    ]);
});

test('does not mark as read for guest', function (): void {
    Email::factory()->create(['id' => '<root@example.com>', 'number' => 301]);

    $this->get('/message/301')->assertOk();

    $this->assertDatabaseCount('user_emails_read', 0);
});
