<?php

declare(strict_types=1);

namespace App\Search;

use Algolia\AlgoliaSearch\Api\SearchClient;
use App\Email\Email;
use DateTime;

class AlgoliaSearchIndex implements SearchIndex
{
    public function __construct(
        private readonly SearchClient $searchClient,
        private readonly string $indexPrefix,
    ) {}

    public function indexEmail(Email $email): void
    {
        $this->searchClient->saveObject(
            $this->indexPrefix.'emails',
            [
                'objectID' => (string) $email->number,
                'subject' => $email->subject,
                'extract' => mb_substr(strip_tags($email->content), 0, 1024),
                'isThreadRoot' => $email->isThreadRoot(),
                'threadId' => $email->threadId,
                'fromEmail' => $email->from->email,
                'fromName' => $email->from->name,
                'date' => $email->date->format(DateTime::ATOM),
                'timestamp' => $email->date->getTimestamp(),
            ]
        );
    }
}
