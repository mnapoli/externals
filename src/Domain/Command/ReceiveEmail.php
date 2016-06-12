<?php

namespace Externals\Domain\Command;

use Imapi\Email;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ReceiveEmail
{
    private $email;

    public function __construct(Email $email)
    {
        $this->email = $email;
    }

    public function getEmail() : Email
    {
        return $this->email;
    }
}
