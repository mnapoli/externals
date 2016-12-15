<?php
/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     Proprietary
 */
declare(strict_types = 1);

namespace Externals\Email;

use AlgoliaSearch\Client;

class EmailSearchIndex
{
    /**
     * @var Client
     */
    private $searchClient;

    public function __construct(Client $searchClient)
    {
        $this->searchClient = $searchClient;
    }

    public function indexEmail(Email $email)
    {
        $index = $this->searchClient->initIndex('emails');

        $index->addObject([
            'subject' => $email->getSubject(),
            'content' => $email->getContent(),
            'originalContent' => $email->getOriginalContent(),
            'threadId' => $email->getThreadId(),
            'fromEmail' => $email->getFrom()->getEmail(),
            'fromName' => $email->getFrom()->getName(),
            'date' => $email->getDate()->format(\DateTime::ATOM),
            'timestamp' => $email->getDate()->getTimestamp(),
        ], $email->getId());
    }
}
