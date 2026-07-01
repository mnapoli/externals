<?php

declare(strict_types=1);

use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('returns 404 when legacy thread id unknown', function (): void {
    $this->get('/thread/123')->assertNotFound();
});

test('returns 404 when no email matches subject', function (): void {
    DB::table('threads_old')->insert(['id' => 1, 'subject' => 'No match']);

    $this->get('/thread/1')->assertNotFound();
});

test('redirects to latest matching thread root', function (): void {
    DB::table('threads_old')->insert(['id' => 1, 'subject' => 'Reused subject']);
    Email::factory()->create([
        'subject' => 'Reused subject',
        'number' => 100,
        'date' => '2020-01-01 00:00:00',
    ]);
    Email::factory()->create([
        'subject' => 'Reused subject',
        'number' => 200,
        'date' => '2024-01-01 00:00:00',
    ]);

    $response = $this->get('/thread/1');

    $response->assertRedirect('/message/200');
    $response->assertStatus(301);
});
