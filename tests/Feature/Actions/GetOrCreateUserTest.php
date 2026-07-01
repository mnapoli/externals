<?php

declare(strict_types=1);

use App\Actions\GetOrCreateUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('should create new user', function (): void {
    $user = app(GetOrCreateUser::class)->handle('abc', 'joe');

    $this->assertSame('abc', $user->githubId);
    $this->assertSame('joe', $user->name);
    $this->assertDatabaseHas('users', ['githubId' => 'abc', 'name' => 'joe']);
});

test('should return existing user', function (): void {
    $existing = User::factory()->create(['githubId' => 'abc', 'name' => 'joe']);

    $user = app(GetOrCreateUser::class)->handle('abc', 'joe');

    $this->assertSame($existing->id, $user->id);
    $this->assertSame('joe', $user->name);
});

test('should update user name when changed', function (): void {
    User::factory()->create(['githubId' => 'abc', 'name' => 'joe']);

    $user = app(GetOrCreateUser::class)->handle('abc', 'jane');

    $this->assertSame('jane', $user->name);
    $this->assertDatabaseHas('users', ['githubId' => 'abc', 'name' => 'jane']);
});
