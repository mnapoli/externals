<?php
declare(strict_types = 1);

namespace Externals\Thread;

use Doctrine\DBAL\Connection;
use Externals\NotFound;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ThreadRepository
{
    /**
     * @var Connection
     */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function findBySubject(string $subject)
    {
        $threadId = $this->db->fetchColumn('SELECT id FROM threads WHERE subject = ?', [$subject]);

        return $threadId ? (int) $threadId : null;
    }

    public function getSubject(int $id) : string
    {
        $subject = $this->db->fetchColumn('SELECT subject FROM threads WHERE id = ?', [$id]);

        if (!$subject) {
            throw new NotFound('No thread found for ID ' . $id);
        }

        return $subject;
    }

    public function create(string $subject) : int
    {
        $this->db->insert('threads', [
            'subject' => $subject,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findLatest(int $page = 1) : array
    {
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $query = 'SELECT threads.id, threads.subject, COUNT(emails.id) as emailCount, MAX(emails.date) as lastUpdate
            FROM threads
            LEFT JOIN emails ON threads.id = emails.threadId
            GROUP BY threads.id
            ORDER BY lastUpdate DESC
            LIMIT ? OFFSET ?';

        return $this->db->fetchAll($query, [$perPage, $offset], [\PDO::PARAM_INT, \PDO::PARAM_INT]);
    }
}
