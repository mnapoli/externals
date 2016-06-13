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

    public function contains(string $emailId) : bool
    {
        return $this->db->fetchColumn('SELECT COUNT(id) FROM emails WHERE id = ?', [$emailId]) > 0;
    }

    /**
     * @return Email[]
     */
    public function findByThread(int $threadId) : array
    {
        $query = 'SELECT * FROM emails WHERE threadId = ? ORDER BY date ASC';
        $emails = $this->db->fetchAll($query, [$threadId]);

        return array_map([$this, 'createEmail'], $emails);
    }

    /**
     * @return Email[]
     */
    public function findAll() : array
    {
        return array_map([$this, 'createEmail'], $this->db->fetchAll('SELECT * FROM emails'));
    }

    public function update(Email $email)
    {
        $this->db->update('emails', [
            'subject' => $email->getSubject(),
            'content' => $email->getContent(),
        ], ['id' => $email->getId()], [
            'text',
            'text',
        ]);
    }

    private function createEmail(array $row) : Email
    {
        return new Email($row['id'], $row['subject'], $row['content'], $row['originalContent']);
    }
}
