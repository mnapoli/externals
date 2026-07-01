<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('logs out authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/');
    $this->assertGuest();
});

test('logout when already guest is safe', function (): void {
    $response = $this->post('/logout');

    $response->assertRedirect('/');
    $this->assertGuest();
});
