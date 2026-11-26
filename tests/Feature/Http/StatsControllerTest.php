<?php

declare(strict_types=1);

use App\Models\Email;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('renders stats with counts', function (): void {
    User::factory()->count(3)->create();
    $root = Email::factory()->create();
    Email::factory()->replyTo($root)->create();
    Email::factory()->replyTo($root)->create();

    $response = $this->get('/stats');

    $response->assertOk();
    $response->assertSeeInOrder(['users', '3', 'thread', '1', 'emails', '3']);
});
