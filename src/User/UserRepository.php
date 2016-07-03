<?php
declare(strict_types = 1);

namespace Externals\User;

use Doctrine\DBAL\Connection;
use Externals\NotFound;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class UserRepository
{
    /**
     * @var Connection
     */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function getOrCreate(string $githubId, string $name) : User
    {
        $userData = $this->db->fetchAssoc('SELECT * FROM users WHERE githubId = ?', [$githubId]);

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
}
