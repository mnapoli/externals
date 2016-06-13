<?php

namespace Externals\Domain\Command;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ReceiveEmail
{
    private $emailId;

    public function __construct(string $emailId)
    {
        $this->emailId = $emailId;
    }

    public function getEmailId() : string
    {
        return $this->emailId;
    }
}
