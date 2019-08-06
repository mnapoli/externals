<?php declare(strict_types=1);

namespace Externals\Email;

/**
 * Parses an email subject.
 */
class EmailSubjectParser
{
    public function sanitize(string $subject): string
    {
        $subject = trim(str_replace('[PHP-DEV]', '', $subject));

        return preg_replace('/^(RE\s?:\s*)+/i', '', $subject);
    }
}
