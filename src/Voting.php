<?php
declare(strict_types = 1);

namespace Externals;

use Doctrine\DBAL\Connection;
use Externals\Email\EmailRepository;

class Voting
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var EmailRepository
     */
    private $emailRepository;

    public function __construct(Connection $db, EmailRepository $emailRepository)
    {
        $this->db = $db;
        $this->emailRepository = $emailRepository;
    }

    /**
     * @return int The new vote value for the email.
     */
    public function vote(int $userId, int $emailNumber, int $value) : int
    {
        $this->db->executeQuery('REPLACE INTO votes (userId, emailNumber, value, updatedAt) VALUES (?, ?, ?, UTC_TIMESTAMP())', [
            $userId,
            $emailNumber,
            $value,
        ]);

        $this->emailRepository->refreshThread($emailNumber);

        return (int) $this->db->fetchColumn('SELECT COALESCE(SUM(votes.value), 0) FROM votes WHERE votes.emailNumber = ?', [
            $emailNumber,
        ]);
    }
}
