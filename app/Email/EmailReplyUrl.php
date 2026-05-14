<?php

declare(strict_types=1);

namespace App\Email;

use ZBateson\MailMimeParser\MailMimeParser;

class EmailReplyUrl
{
    private const string MAILING_LIST_ADDRESS = 'internals@lists.php.net';

    public static function build(Email $email): string
    {
        $subject = $email->subject;
        if (! preg_match('/^Re:/i', $subject)) {
            $subject = 'Re: '.$subject;
        }

        $parsedEmail = (new MailMimeParser)->parse($email->source, false);

        $references = $parsedEmail->getHeaderValue('References');
        $messageId = $email->id;
        $newReferences = $references ? trim($references).' '.$messageId : $messageId;

        $fromName = $email->from->getNameOrEmail();
        $date = $email->date->format('D, d M Y H:i:s O');

        $originalContent = $parsedEmail->getTextContent();
        $quotedContent = '';
        if ($originalContent) {
            $quotedContent = implode("\n", array_map(
                fn ($line) => '> '.$line,
                explode("\n", trim($originalContent))
            ));
        }

        $body = sprintf("On %s, %s wrote:\n%s\n\n", $date, $fromName, $quotedContent);

        $params = [
            'subject' => $subject,
            'body' => $body,
            'In-Reply-To' => $messageId,
            'References' => $newReferences,
        ];

        return 'mailto:'.self::MAILING_LIST_ADDRESS.'?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
