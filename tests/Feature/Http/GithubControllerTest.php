<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\GithubController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

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

test('callback stores the verified addresses of the GitHub account', function (): void {
    Socialite::fake('github', (new SocialiteUser)->setToken('token')->map([
        'id' => '12345',
        'nickname' => 'octocat',
        'email' => 'octocat@github.com',
    ]));
    Http::fake(['https://api.github.com/user/emails' => Http::response([
        ['email' => 'octocat@github.com', 'primary' => true, 'verified' => true],
        ['email' => 'octocat@php.net', 'primary' => false, 'verified' => true],
        ['email' => 'unverified@example.com', 'primary' => false, 'verified' => false],
    ])]);

    $this->get('/login?code=abc');

    $user = User::where('githubId', '12345')->firstOrFail();
    expect($user->email)->toBe('octocat@github.com');
    expect(DB::table('user_verified_emails')->where('userId', $user->id)->orderBy('email')->pluck('email')->all())
        ->toBe(['octocat@github.com', 'octocat@php.net']);
    Http::assertSent(fn($request) => $request->hasHeader('Authorization', 'Bearer token'));
});

test('callback still logs in when GitHub does not list the addresses', function (): void {
    Socialite::fake('github', (new SocialiteUser)->setToken('token')->map(['id' => '12345', 'nickname' => 'octocat']));
    Http::fake(['https://api.github.com/user/emails' => Http::response('', 500)]);

    $this->get('/login?code=abc')->assertRedirect('/');

    $this->assertAuthenticated();
    $this->assertDatabaseCount('user_verified_emails', 0);
});

test('existing accounts are told once that their address is now stored', function (): void {
    User::factory()->create(['githubId' => '12345', 'name' => 'octocat']);
    Socialite::fake('github', (new SocialiteUser)->map(['id' => '12345', 'nickname' => 'octocat', 'email' => 'octocat@github.com']));

    $this->get('/login?code=abc')->assertSessionHas('status', sprintf(GithubController::EMAIL_STORED_MESSAGE, 'octocat@github.com'));
    $this->get('/')->assertSee('Externals now stores the email addresses of your GitHub account (octocat@github.com)');
    $this->get('/')->assertDontSee('now stores the email');

    auth()->logout();
    $this->get('/login?code=abc')->assertSessionMissing('status');
});

test('new accounts are not told that their address is stored', function (): void {
    Socialite::fake('github', (new SocialiteUser)->map(['id' => '12345', 'nickname' => 'octocat', 'email' => 'octocat@github.com']));

    $this->get('/login?code=abc')->assertSessionMissing('status');
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
