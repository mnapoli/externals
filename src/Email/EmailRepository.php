<?php
declare(strict_types = 1);

namespace Externals\Email;

use Doctrine\DBAL\Connection;
use Externals\NotFound;
use Externals\User\User;

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
     * Returns a threaded view of the emails.
     *
     * @return ThreadItem[]
     */
    public function getThreadView(int $threadId, User $user = null) : array
    {
        $qb = $this->db->createQueryBuilder();
        $qb->select('emails.*')
            ->from('emails')
            ->where('emails.threadId = :threadId')
            ->orderBy('emails.date', 'ASC')
            ->setParameter('threadId', $threadId);

        if ($user) {
            $qb->addSelect('IF(readStatus.userId, 1, 0) as wasRead')
                ->leftJoin('emails', 'user_emails_read', 'readStatus', 'emails.id = readStatus.emailId AND readStatus.userId = :userId');
            $qb->setParameter('userId', $user->getId());
        } else {
            $qb->addSelect('0 as wasRead');
        }

        /** @var Email[] $emails */
        $emails = array_map(
            [$this, 'emailFromRow'],
            $qb->execute()->fetchAll()
        );

        // Index by ID
        /** @var ThreadItem[] $indexedThreadItem */
        $indexedThreadItem = [];
        foreach ($emails as $email) {
            $indexedThreadItem[$email->getId()] = new ThreadItem($email);
        }

        // Link each email to the one it replies to
        $rootItems = [];
        foreach ($indexedThreadItem as $item) {
            $replyId = $item->getEmail()->getInReplyTo();
            if ($replyId && isset($indexedThreadItem[$replyId])) {
                $indexedThreadItem[$replyId]->addReply($item);
            } else {
                $rootItems[] = $item;
            }
        }

        return $rootItems;
    }

    /**
     * Returns the number of emails in a thread.
     */
    public function getThreadCount(int $threadId) : int
    {
        return (int) $this->db->fetchColumn('SELECT COUNT(id) FROM emails WHERE threadId = ?', [$threadId]);
    }

    /**
     * @return Email[]
     */
    public function findAll() : array
    {
        return array_map([$this, 'emailFromRow'], $this->db->fetchAll('SELECT * FROM emails'));
    }

    public function add(Email $email)
    {
        $this->db->insert('emails', [
            'id' => $email->getId(),
            'number' => $email->getNumber(),
            'content' => $email->getContent(),
            'originalContent' => $email->getOriginalContent(),
            'threadId' => $email->getThreadId(),
            'date' => $email->getDate(),
            'fromEmail' => $email->getFrom()->getEmail(),
            'fromName' => $email->getFrom()->getName(),
            'inReplyTo' => $email->getInReplyTo(),
        ], [
            'string',
            'integer',
            'text',
            'text',
            'integer',
            'datetime',
            'string',
            'string',
            'integer',
        ]);
    }

    public function getEmailSource(int $number) : string
    {
        $email = $this->db->fetchAssoc('SELECT * FROM emails WHERE `number` = ?', [$number]);
        if (!$email) {
            throw new NotFound('Email not found');
        }
        $email = $this->emailFromRow($email);
        return $email->getOriginalContent();
    }

    public function getLastEmailNumber() : int
    {
        return (int) $this->db->fetchColumn('SELECT MAX(number) FROM emails');
    }

    public function updateContent(Email $email)
    {
        $this->db->update('emails', [
            'content' => $email->getContent(),
        ], ['id' => $email->getId()], ['text']);
    }

    public function getEmailCount() : int
    {
        return (int) $this->db->fetchColumn('SELECT COUNT(*) FROM emails');
    }

    private function emailFromRow(array $row) : Email
    {
        $date = $row['date'];
        if (is_string($date)) {
            $date = new \DateTimeImmutable($date);
        }

        $email = new Email(
            $row['id'],
            (int) $row['number'],
            $row['content'],
            $row['originalContent'],
            (int) $row['threadId'],
            $date,
            new EmailAddress($row['fromEmail'], $row['fromName']),
            $row['inReplyTo']
        );

        if (array_key_exists('wasRead', $row) && $row['wasRead']) {
            $email->markAsRead();
        }

        return $email;
    }
}
