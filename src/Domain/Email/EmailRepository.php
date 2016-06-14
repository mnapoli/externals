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

    public function add(Email $email)
    {
        $this->db->insert('emails', [
            'id' => $email->getId(),
            'subject' => $email->getSubject(),
            'content' => $email->getContent(),
            'originalContent' => $email->getOriginalContent(),
            'threadId' => $email->getThreadId(),
            'date' => $email->getDate(),
            'fromEmail' => $email->getFrom()->getEmail(),
            'fromName' => $email->getFrom()->getName(),
        ], [
            'string',
            'text',
            'text',
            'text',
            'integer',
            'datetime',
            'string',
            'string',
        ]);
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
        $date = $row['date'];
        if (is_string($date)) {
            $date = new \DateTimeImmutable($date);
        }

        return new Email(
            $row['id'],
            $row['subject'],
            $row['content'],
            $row['originalContent'],
            (int) $row['threadId'],
            $date,
            new EmailAddress($row['fromEmail'], $row['fromName'])
        );
    }
}
