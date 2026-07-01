<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use RuntimeException;

uses(RefreshDatabase::class);

test('authenticated user is redirected home', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/login');

    $response->assertRedirect('/');
});

test('unauthenticated user without code is redirected to github', function (): void {
    Socialite::fake('github');

    $response = $this->get('/login');

    $response->assertRedirect('https://socialite.fake/github/authorize');
});

test('callback with code creates user and logs in', function (): void {
    Socialite::fake('github', (new SocialiteUser)->map([
        'id' => '12345',
        'nickname' => 'octocat',
    ]));

    $response = $this->get('/login?code=abc');

    $response->assertRedirect('/');
    $user = User::where('githubId', '12345')->firstOrFail();
    $this->assertDatabaseHas('users', ['githubId' => '12345', 'name' => 'octocat']);
    $this->assertAuthenticatedAs($user);
    $this->assertNotNull($user->remember_token);
    $this->assertSame(60, mb_strlen($user->remember_token));
});

test('callback logs in existing user', function (): void {
    $existing = User::factory()->create(['githubId' => '12345', 'name' => 'octocat']);
    Socialite::fake('github', (new SocialiteUser)->map([
        'id' => '12345',
        'nickname' => 'octocat',
    ]));

    $response = $this->get('/login?code=abc');

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($existing);
    $this->assertSame(1, User::count());
});

test('callback with invalid state renders error view', function (): void {
    Socialite::fake('github', fn() => throw new InvalidStateException);

    $response = $this->get('/login?code=abc');

    $response->assertStatus(400);
    $response->assertViewIs('auth.login-error');
    $response->assertViewHas('error', 'Invalid state');
    $this->assertGuest();
});

test('callback with socialite failure renders error view', function (): void {
    Socialite::fake('github', fn() => throw new RuntimeException('boom'));

    $response = $this->get('/login?code=abc');

    $response->assertStatus(400);
    $response->assertViewIs('auth.login-error');
    $response->assertViewHas('error', 'boom');
    $this->assertGuest();
});
