<?php
declare(strict_types = 1);

namespace Externals\Thread;

use Doctrine\DBAL\Connection;
use Externals\NotFound;
use Externals\User\User;

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

        return (string) $subject;
    }

    public function create(string $subject) : int
    {
        $this->db->insert('threads', [
            'subject' => $subject,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findLatest(int $page = 1, User $user = null) : array
    {
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $qb = $this->db->createQueryBuilder();
        $qb->select('threads.id', 'threads.subject', 'COUNT(emails.id) as emailCount', 'MAX(emails.date) as lastUpdate')
            ->from('threads')
            ->leftJoin('threads', 'emails', 'emails', 'threads.id = emails.threadId')
            ->groupBy('threads.id')
            ->orderBy('lastUpdate', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($perPage);

        if ($user) {
            $qb->addSelect('readStatus.emailsRead')
                ->leftJoin('threads', 'user_threads_read', 'readStatus', 'threads.id = readStatus.threadId AND readStatus.userId = :userId');
            $qb->setParameter('userId', $user->getId());
        }

        return $qb->execute()->fetchAll();
    }

    public function markThreadRead(int $threadId, User $user, int $emailCount)
    {
        $this->db->executeQuery('REPLACE INTO user_threads_read (userId, threadId, emailsRead) VALUES (?, ?, ?)', [
            $user->getId(),
            $threadId,
            $emailCount,
        ]);
    }
}
