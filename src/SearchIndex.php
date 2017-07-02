<?php
/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     Proprietary
 */
declare(strict_types = 1);

namespace Externals;

use AlgoliaSearch\Client;
use Externals\Email\Email;

class SearchIndex
{
    /**
     * @var Client
     */
    private $searchClient;

    /**
     * @var string
     */
    private $indexPrefix;

    public function __construct(Client $searchClient, string $indexPrefix)
    {
        $this->searchClient = $searchClient;
        $this->indexPrefix = $indexPrefix;
    }

    public function indexEmail(Email $email, string $threadSubject)
    {
        $index = $this->searchClient->initIndex($this->indexPrefix . 'emails');

        $index->addObject([
            'subject' => $threadSubject,
            'extract' => mb_substr(strip_tags($email->getContent()), 0, 1024),
            'threadId' => $email->getThreadId(),
            'fromEmail' => $email->getFrom()->getEmail(),
            'fromName' => $email->getFrom()->getName(),
            'date' => $email->getDate()->format(\DateTime::ATOM),
            'timestamp' => $email->getDate()->getTimestamp(),
        ], $email->getNumber());
    }

    public function indexThread(int $id, string $subject)
    {
        $index = $this->searchClient->initIndex($this->indexPrefix . 'threads');

        $index->addObject([
            'subject' => $subject,
        ], $id);
    }
}
