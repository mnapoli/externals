<?php declare(strict_types=1);

namespace Externals\Test\User;

use Doctrine\DBAL\Connection;
use Externals\User\UserRepository;
use PHPUnit\Framework\TestCase;

class UserRepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function should_get_existing_user()
    {
        $db = $this->createMock(Connection::class);
        $repository = new UserRepository($db);
        $db->method('fetchAssociative')
            ->willReturn([
                'id' => 123,
                'githubId' => 'abc',
                'name' => 'joe',
            ]);
        $db->expects($this->never())->method('update');

        $user = $repository->getOrCreate('abc', 'joe');

        $this->assertEquals(123, $user->id);
        $this->assertEquals('abc', $user->githubId);
        $this->assertEquals('joe', $user->name);
    }

    /**
     * @test
     */
    public function should_create_new_user()
    {
        $db = $this->createMock(Connection::class);
        $repository = new UserRepository($db);
        $db->expects($this->once())
            ->method('insert')
            ->with('users', ['githubId' => 'abc', 'name' => 'joe']);
        $db->method('lastInsertId')
            ->willReturn(123);
        $db->expects($this->never())->method('update');

        $user = $repository->getOrCreate('abc', 'joe');

        $this->assertEquals(123, $user->id);
        $this->assertEquals('abc', $user->githubId);
        $this->assertEquals('joe', $user->name);
    }

    /**
     * @test
     */
    public function should_update_user_name(): void
    {
        $db = $this->createMock(Connection::class);
        $repository = new UserRepository($db);
        $db->method('fetchAssociative')
           ->willReturn([
               'id' => 123,
               'githubId' => 'abc',
               'name' => 'joe',
           ]);

        $db->expects($this->once())
            ->method('update')
            ->with('users', ['name' => 'jane'], ['id' => 123])
            ->willReturn(1);

        $user = $repository->getOrCreate('abc', 'jane');

        $this->assertEquals(123, $user->id);
        $this->assertEquals('abc', $user->githubId);
        $this->assertEquals('jane', $user->name);
    }
}
