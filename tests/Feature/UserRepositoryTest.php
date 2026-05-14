<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\User\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(UserRepository::class);
    }

    public function test_should_create_new_user(): void
    {
        $user = $this->repository->getOrCreate('abc', 'joe');

        $this->assertSame('abc', $user->githubId);
        $this->assertSame('joe', $user->name);
        $this->assertDatabaseHas('users', ['githubId' => 'abc', 'name' => 'joe']);
    }

    public function test_should_return_existing_user(): void
    {
        $existing = User::create(['githubId' => 'abc', 'name' => 'joe']);

        $user = $this->repository->getOrCreate('abc', 'joe');

        $this->assertSame($existing->id, $user->id);
        $this->assertSame('joe', $user->name);
    }

    public function test_should_update_user_name_when_changed(): void
    {
        User::create(['githubId' => 'abc', 'name' => 'joe']);

        $user = $this->repository->getOrCreate('abc', 'jane');

        $this->assertSame('jane', $user->name);
        $this->assertDatabaseHas('users', ['githubId' => 'abc', 'name' => 'jane']);
    }
}
