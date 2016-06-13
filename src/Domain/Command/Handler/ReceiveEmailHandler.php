<?php
declare(strict_types = 1);

namespace Externals\Domain\Command\Handler;

use Doctrine\DBAL\Connection;
use Externals\Domain\Command\ReceiveEmail;
use Externals\Domain\Email\EmailSubjectParser;
use Externals\Domain\Thread\ThreadRepository;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ReceiveEmailHandler
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var ThreadRepository
     */
    private $threadRepository;

    /**
     * @var EmailSubjectParser
     */
    private $emailSubjectParser;

    public function __construct(Connection $db, ThreadRepository $threadRepository, EmailSubjectParser $emailSubjectParser)
    {
        $this->db = $db;
        $this->threadRepository = $threadRepository;
        $this->emailSubjectParser = $emailSubjectParser;
    }

    public function __invoke(ReceiveEmail $receiveEmail)
    {
        $email = $receiveEmail->getEmail();

        // Check if we have already received the email
        $emailId = $this->db->fetchColumn('SELECT id FROM emails WHERE emailUniqueId = ?', [$email->getId()]);
        if ($emailId) {
            return;
        }

        $threadSubject = $this->emailSubjectParser->sanitize($email->getSubject());

        $threadId = $this->threadRepository->findBySubject($threadSubject);
        if (!$threadId) {
            // New thread
            $threadId = $this->threadRepository->create($threadSubject);
        }

        $this->db->insert('emails', [
            'emailUniqueId' => $email->getId(),
            'subject' => $email->getSubject(),
            'content' => $email->getTextContent(),
            'threadId' => $threadId,
            'date' => $email->getDate(),
        ], [
            'string',
            'text',
            'text',
            'integer',
            'datetime',
        ]);
    }
}
