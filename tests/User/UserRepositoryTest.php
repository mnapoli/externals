<?php
declare(strict_types = 1);

namespace Externals\Test\User;

use Doctrine\DBAL\Connection;
use Externals\User\UserRepository;

class UserRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function should_get_existing_user()
    {
        $db = $this->createMock(Connection::class);
        $repository = new UserRepository($db);
        $db->method('fetchAssoc')
            ->willReturn([
                'id' => 123,
                'githubId' => 'abc',
                'name' => 'joe',
            ]);

        $user = $repository->getOrCreate('abc', 'joe');

        $this->assertEquals(123, $user->getId());
        $this->assertEquals('abc', $user->getGithubId());
        $this->assertEquals('joe', $user->getName());
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

        $user = $repository->getOrCreate('abc', 'joe');

        $this->assertEquals(123, $user->getId());
        $this->assertEquals('abc', $user->getGithubId());
        $this->assertEquals('joe', $user->getName());
    }
}
