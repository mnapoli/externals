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

    public function getById(string $id) : Email
    {
        $row = $this->db->fetchAssoc('SELECT * FROM emails WHERE id = ?', [$id]);
        if (!$row) {
            throw new NotFound("Email $id was not found");
        }
        return $this->emailFromRow($row);
    }

    public function getByNumber(int $number) : Email
    {
        $row = $this->db->fetchAssoc('SELECT * FROM emails WHERE number = ?', [$number], ['integer']);
        if (!$row) {
            throw new NotFound("Email $number was not found");
        }
        return $this->emailFromRow($row);
    }

    /**
     * Returns a threaded view of the emails.
     *
     * @return ThreadItem[]
     */
    public function getThreadView(Email $email, User $user = null) : array
    {
        $qb = $this->db->createQueryBuilder();
        $qb->select('emails.*')
            ->from('emails')
            ->where('emails.threadId = :threadId')
            ->orderBy('emails.date', 'ASC')
            ->setParameter('threadId', $email->getId());

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

    public function findLatestThreads(int $page = 1, User $user = null) : array
    {
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $qb = $this->db->createQueryBuilder();
        $qb->select('threads.number', 'threads.subject', 'COUNT(threadEmails.id) as emailCount', 'MAX(threadEmails.date) as lastUpdate')
            ->from('emails', 'threads')
            ->leftJoin('threads', 'emails', 'threadEmails', 'threads.id = threadEmails.threadId')
            ->where('threads.threadId = threads.id')
            ->groupBy('threads.id')
            ->orderBy('lastUpdate', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($perPage);

        if ($user) {
            $qb->addSelect('readStatus.emailsRead as emailsRead')
                ->leftJoin('threads', 'user_threads_read', 'readStatus', 'threads.id = readStatus.threadId AND readStatus.userId = :userId');
            $qb->setParameter('userId', $user->getId());
        } else {
            $qb->addSelect('0 as emailsRead');
        }

        return $qb->execute()->fetchAll();
    }

    /**
     * Returns the number of emails in a thread.
     */
    public function getThreadSize(Email $email) : int
    {
        $numberOfEmails = (int) $this->db->fetchColumn('SELECT COUNT(id) FROM emails WHERE threadId = ?', [$email->getId()]);

        return $numberOfEmails;
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
            'subject' => $email->getSubject(),
            'content' => $email->getContent(),
            'source' => $email->getSource(),
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
            'text',
            'string',
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
        return $email->getSource();
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

    public function getThreadCount() : int
    {
        return (int) $this->db->fetchColumn('SELECT COUNT(*) FROM emails WHERE threadId = id');
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
            $row['subject'],
            $row['content'],
            $row['source'],
            $row['threadId'],
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
