<?php declare(strict_types=1);

namespace Externals\User;

use Doctrine\DBAL\Connection;

class UserRepository
{
    public function __construct(
        private Connection $db,
    ) {
    }

    public function getOrCreate(string $githubId, string $name): User
    {
        $userData = $this->db->fetchAssociative('SELECT * FROM users WHERE githubId = ?', [$githubId]);

        if ($userData) {
            return new User((int) $userData['id'], $githubId, (string) $userData['name']);
        }

        $this->db->insert('users', [
            'githubId' => $githubId,
            'name' => $name,
        ]);

        $id = (int) $this->db->lastInsertId();

        return new User($id, $githubId, $name);
    }

    public function getUserCount(): int
    {
        return (int) $this->db->fetchOne('SELECT COUNT(*) FROM users');
    }
}
