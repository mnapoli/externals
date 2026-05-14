<?php

declare(strict_types=1);

namespace App\Voting;

use App\Email\EmailRepository;
use Illuminate\Database\ConnectionInterface;

class Voting
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly EmailRepository $emailRepository,
    ) {}

    /**
     * @return int The new total vote value for the email.
     */
    public function vote(int $userId, int $emailNumber, int $value): int
    {
        $this->db->statement(
            'REPLACE INTO votes (userId, emailNumber, value, updatedAt) VALUES (?, ?, ?, UTC_TIMESTAMP())',
            [$userId, $emailNumber, $value]
        );

        $this->emailRepository->refreshThread($emailNumber);

        $row = $this->db->selectOne(
            'SELECT COALESCE(SUM(votes.value), 0) AS total FROM votes WHERE votes.emailNumber = ?',
            [$emailNumber]
        );

        return (int) ($row->total ?? 0);
    }
}
