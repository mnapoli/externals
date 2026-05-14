<?php

declare(strict_types=1);

namespace Feature\Actions;

use App\Actions\GetOrCreateUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetOrCreateUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_create_new_user(): void
    {
        $user = app(GetOrCreateUser::class)->handle('abc', 'joe');

        $this->assertSame('abc', $user->githubId);
        $this->assertSame('joe', $user->name);
        $this->assertDatabaseHas('users', ['githubId' => 'abc', 'name' => 'joe']);
    }

    public function test_should_return_existing_user(): void
    {
        $existing = User::create(['githubId' => 'abc', 'name' => 'joe']);

        $user = app(GetOrCreateUser::class)->handle('abc', 'joe');

        $this->assertSame($existing->id, $user->id);
        $this->assertSame('joe', $user->name);
    }

    public function test_should_update_user_name_when_changed(): void
    {
        User::create(['githubId' => 'abc', 'name' => 'joe']);

        $user = app(GetOrCreateUser::class)->handle('abc', 'jane');

        $this->assertSame('jane', $user->name);
        $this->assertDatabaseHas('users', ['githubId' => 'abc', 'name' => 'jane']);
    }
}
