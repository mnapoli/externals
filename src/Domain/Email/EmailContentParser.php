<?php

namespace Externals\Domain\Email;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class EmailContentParser
{
    const FOOTER = [
        'PHP Internals - PHP Runtime Development Mailing List',
        'To unsubscribe, visit: http://www.php.net/unsub.php',
    ];

    public function parse(string $content) : string
    {
        $content = str_replace(self::FOOTER, '', $content);

        return trim($content, " \t\n\r\0\x0B->");
    }
}
