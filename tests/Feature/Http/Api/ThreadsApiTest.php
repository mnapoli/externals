<?php

declare(strict_types=1);

use App\Models\Email;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function threadWithReply(): Email
{
    $root = Email::factory()->create([
        'number' => 100,
        'subject' => '[RFC] Something',
        'fromName' => 'Alice',
        'date' => '2026-01-01 10:00:00',
        'source' => "Subject: Test\r\n\r\nPlease review.\r\n",
    ]);
    Email::factory()->replyTo($root)->create([
        'number' => 101,
        'fromName' => 'Bob',
        'date' => '2026-01-02 10:00:00',
        'source' => "Subject: Re: Test\r\n\r\n> Please review.\r\n\r\nLooks good.\r\n",
    ]);
    Thread::factory()->create([
        'emailId' => $root->id,
        'emailNumber' => 100,
        'emailCount' => 2,
        'lastUpdate' => '2026-01-02 10:00:00',
        'votes' => 3,
    ]);

    return $root;
}

test('lists the latest threads', function (): void {
    threadWithReply();

    $response = $this->getJson('/api/threads');

    $response->assertOk();
    $response->assertHeader('Cache-Control', 'max-age=60, public');
    $response->assertJsonPath('meta', ['sort' => 'latest', 'page' => 1, 'perPage' => 20, 'pageCount' => 1]);
    $response->assertJsonPath('links', ['prev' => null, 'next' => null]);
    $response->assertJsonPath('data.0', [
        'number' => 100,
        'subject' => '[RFC] Something',
        'author' => 'Alice',
        'date' => '2026-01-01T10:00:00+00:00',
        'lastActivity' => '2026-01-02T10:00:00+00:00',
        'messageCount' => 2,
        'votes' => 3,
        'url' => url('/message/100'),
        'markdownUrl' => url('/message/100.md'),
        'apiUrl' => url('/api/threads/100'),
    ]);
});

test('lists the top threads', function (): void {
    foreach ([200 => 0, 201 => 5] as $number => $votes) {
        $email = Email::factory()->create(['number' => $number]);
        Thread::factory()->create(['emailId' => $email->id, 'emailNumber' => $number, 'votes' => $votes, 'lastUpdate' => now()]);
    }

    $response = $this->getJson('/api/threads?sort=top');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.number', 201);
});

test('rejects invalid list parameters', function (): void {
    $this->get('/api/threads?sort=oldest')->assertUnprocessable()->assertJsonValidationErrors('sort');
    $this->get('/api/threads?page=0')->assertUnprocessable()->assertJsonValidationErrors('page');
});

test('returns a thread with its messages as markdown', function (): void {
    threadWithReply();

    $response = $this->getJson('/api/threads/100');

    $response->assertOk();
    $response->assertJsonPath('data.subject', '[RFC] Something');
    $response->assertJsonPath('data.messageCount', 2);
    $response->assertJsonPath('data.votes', 3);
    $response->assertJsonPath('data.archiveUrl', 'https://news-web.php.net/php.internals/100');
    $response->assertJsonPath('data.messages.0', [
        'number' => 100,
        'author' => 'Alice',
        'date' => '2026-01-01T10:00:00+00:00',
        'inReplyTo' => null,
        'url' => url('/message/100#100'),
        'content' => 'Please review.',
    ]);
    $response->assertJsonPath('data.messages.1.inReplyTo', 100);
    $response->assertJsonPath('data.messages.1.content', "> Please review.\n\nLooks good.");
});

test('redirects a reply to its thread', function (): void {
    threadWithReply();

    $this->getJson('/api/threads/101')->assertRedirect('/api/threads/100');
});

test('returns a json 404 for unknown threads', function (): void {
    $this->get('/api/threads/12345')->assertNotFound()->assertJsonStructure(['message']);
});
