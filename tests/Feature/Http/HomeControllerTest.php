<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('renders home for guest', function (): void {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('mailing list to the outside', false);
    $response->assertSee('id="search-input"', false);
});

test('renders home for authenticated user', function (): void {
    $user = User::factory()->create(['name' => 'octocat']);

    $response = $this->actingAs($user)->get('/');

    $response->assertOk();
    // The nav greets the logged-in user by name.
    $response->assertSee('octocat');
});
