<?php

declare(strict_types=1);

use App\Models\Email;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('redirects to login when unauthenticated', function (): void {
    $this->get('/email/42/source')->assertRedirect('/login');
});

test('returns 404 when email does not exist', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/email/9999/source')
        ->assertNotFound();
});

test('returns raw source as plain text', function (): void {
    Email::factory()->create(['number' => 42, 'source' => "Subject: hi\r\n\r\nhello"]);

    $response = $this->actingAs(User::factory()->create())
        ->get('/email/42/source');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
    $this->assertSame("Subject: hi\r\n\r\nhello", $response->getContent());
});
