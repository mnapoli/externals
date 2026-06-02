<?php

declare(strict_types=1);

namespace Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use RuntimeException;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_is_redirected_home(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/');
    }

    public function test_unauthenticated_user_without_code_is_redirected_to_github(): void
    {
        Socialite::fake('github');

        $response = $this->get('/login');

        $response->assertRedirect('https://socialite.fake/github/authorize');
    }

    public function test_callback_with_code_creates_user_and_logs_in(): void
    {
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
    }

    public function test_callback_logs_in_existing_user(): void
    {
        $existing = User::factory()->create(['githubId' => '12345', 'name' => 'octocat']);
        Socialite::fake('github', (new SocialiteUser)->map([
            'id' => '12345',
            'nickname' => 'octocat',
        ]));

        $response = $this->get('/login?code=abc');

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::count());
    }

    public function test_callback_with_invalid_state_renders_error_view(): void
    {
        Socialite::fake('github', fn() => throw new InvalidStateException);

        $response = $this->get('/login?code=abc');

        $response->assertStatus(400);
        $response->assertViewIs('auth.login-error');
        $response->assertViewHas('error', 'Invalid state');
        $this->assertGuest();
    }

    public function test_callback_with_socialite_failure_renders_error_view(): void
    {
        Socialite::fake('github', fn() => throw new RuntimeException('boom'));

        $response = $this->get('/login?code=abc');

        $response->assertStatus(400);
        $response->assertViewIs('auth.login-error');
        $response->assertViewHas('error', 'boom');
        $this->assertGuest();
    }
}
