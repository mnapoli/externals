<?php
declare(strict_types = 1);

namespace Externals\Search;

use AlgoliaSearch\Client;
use Externals\Email\Email;

class AlgoliaSearchIndex implements SearchIndex
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

    public function indexEmail(Email $email) : void
    {
        $index = $this->searchClient->initIndex($this->indexPrefix . 'emails');

        $index->addObject([
            'subject' => $email->getSubject(),
            'extract' => mb_substr(strip_tags($email->getContent()), 0, 1024),
            'isThreadRoot' => $email->isThreadRoot(),
            'threadId' => $email->getThreadId(),
            'fromEmail' => $email->getFrom()->getEmail(),
            'fromName' => $email->getFrom()->getName(),
            'date' => $email->getDate()->format(\DateTime::ATOM),
            'timestamp' => $email->getDate()->getTimestamp(),
        ], $email->getNumber());
    }
}
