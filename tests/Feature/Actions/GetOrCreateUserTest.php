<?php

declare(strict_types=1);

use App\Actions\GetOrCreateUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

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

function verifiedEmailsOf(User $user): array
{
    return DB::table('user_verified_emails')->where('userId', $user->id)->orderBy('email')->pluck('email')->all();
}

test('creates a user with the addresses of their GitHub account', function (): void {
    $user = app(GetOrCreateUser::class)->handle('abc', 'joe', 'joe@example.com', ['joe@example.com', 'Joe@PHP.net']);

    $this->assertDatabaseHas('users', ['githubId' => 'abc', 'name' => 'joe', 'email' => 'joe@example.com']);
    expect(verifiedEmailsOf($user))->toBe(['joe@example.com', 'joe@php.net']);
});

test('updates the username and the addresses of an existing user', function (): void {
    $existing = User::factory()->create(['githubId' => 'abc', 'name' => 'joe', 'email' => 'old@example.com']);
    app(GetOrCreateUser::class)->handle('abc', 'joe', 'old@example.com', ['old@example.com', 'gone@example.com']);

    $user = app(GetOrCreateUser::class)->handle('abc', 'jane', 'new@example.com', ['new@example.com']);

    expect($user->id)->toBe($existing->id);
    $this->assertDatabaseHas('users', ['githubId' => 'abc', 'name' => 'jane', 'email' => 'new@example.com']);
    expect(verifiedEmailsOf($user))->toBe(['new@example.com']);
});

test('keeps the addresses it has when GitHub sends none', function (): void {
    app(GetOrCreateUser::class)->handle('abc', 'joe', 'joe@example.com', ['joe@example.com']);

    $user = app(GetOrCreateUser::class)->handle('abc', 'joe');

    expect($user->email)->toBe('joe@example.com');
    expect(verifiedEmailsOf($user))->toBe(['joe@example.com']);
});
