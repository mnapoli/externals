<?php
declare(strict_types = 1);

namespace Externals\Domain\Email;

use Doctrine\DBAL\Connection;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class EmailRepository
{
    /**
     * @var Connection
     */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @return Email[]
     */
    public function findByThread(int $threadId) : array
    {
        $query = 'SELECT id, subject, content FROM emails WHERE threadId = ? ORDER BY date DESC';
        $emails = $this->db->fetchAll($query, [$threadId]);

        return array_map(function (array $row) : Email {
            return new Email($row['id'], $row['subject'], $row['content']);
        }, $emails);
    }
}
