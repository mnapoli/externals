<?php

namespace Externals\Domain\Email;

/**
 * Parses an email subject.
 */
class EmailSubjectParser
{
    public function sanitize($subject) : string
    {
        return preg_replace('/^RE\s?:\s/i', '', $subject);
    }
}
