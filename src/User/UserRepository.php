<?php declare(strict_types=1);

namespace Externals\User;

use Doctrine\DBAL\Connection;

class UserRepository
{
    public function __construct(
        private Connection $db,
    ) {
    }

    public function getOrCreate(string $githubId, string $ghName): User
    {
        $userData = $this->db->fetchAssociative('SELECT * FROM users WHERE githubId = ?', [$githubId]);

        if ($userData) {
            $id = (int) $userData['id'];

            if ($ghName !== (string) $userData['name']) {
                $this->db->update('users', ['name' => $ghName], ['id' => $id]);
            }

            return new User($id, $githubId, $ghName);
        }

        $this->db->insert('users', [
            'githubId' => $githubId,
            'name' => $ghName,
        ]);

        $id = (int) $this->db->lastInsertId();

        return new User($id, $githubId, $ghName);
    }

    public function getUserCount(): int
    {
        return (int) $this->db->fetchOne('SELECT COUNT(*) FROM users');
    }
}
