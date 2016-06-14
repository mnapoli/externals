<?php
declare(strict_types = 1);

namespace Externals\Domain\Email;

use Misd\Linkify\Linkify;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class EmailContentParser
{
    const FOOTER = [
        'PHP Internals - PHP Runtime Development Mailing List',
        'To unsubscribe, visit: http://www.php.net/unsub.php',
    ];
    const QUOTE = '>';

    /**
     * @var Linkify
     */
    private $linkify;

    public function __construct(Linkify $linkify)
    {
        $this->linkify = $linkify;
    }

    public function parse(string $content) : string
    {
        $content = str_replace(self::FOOTER, '', $content);
        $content = trim($content, " \t\n\r\0\x0B->");

        $content = $this->linkify->process($content, [
            'attr' => ['rel' => 'nofollow'],
        ]);

        $lines = preg_split('/\R/', $content); // explode all lines

        $lines = array_map(function (string $line) : string {
            $line = trim($line);
            if (substr($line, 0, 1) === self::QUOTE) {
                $line = "<span class='quoted-line'>$line</span>";
            }
            return $line;
        }, $lines);

        $content = implode("<br>\n", $lines);

        return $content;
    }
}
