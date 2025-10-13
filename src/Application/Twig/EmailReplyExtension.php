<?php declare(strict_types=1);

namespace Externals\Application\Twig;

use Externals\Email\Email;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use ZBateson\MailMimeParser\MailMimeParser;

class EmailReplyExtension extends AbstractExtension
{
    private const MAILING_LIST_ADDRESS = 'internals@lists.php.net';

    public function getFunctions(): array
    {
        return [
            new TwigFunction('email_reply_url', [$this, 'generateReplyUrl']),
        ];
    }

    public function generateReplyUrl(Email $email): string
    {
        $to = self::MAILING_LIST_ADDRESS;

        // Prepare subject with "Re:" prefix if not already present
        $subject = $email->getSubject();
        if (!preg_match('/^Re:/i', $subject)) {
            $subject = 'Re: ' . $subject;
        }

        // Parse the email source to extract headers
        $parser = new MailMimeParser();
        $parsedEmail = $parser->parse($email->getSource(), false);

        // Get the References header from the original email
        $references = $parsedEmail->getHeaderValue('References');
        $messageId = $email->getId();

        // Build the new References header
        // It should include all previous references plus the message we're replying to
        if ($references) {
            $newReferences = trim($references) . ' ' . $messageId;
        } else {
            $newReferences = $messageId;
        }

        // Prepare the body with quoted original message
        $fromName = $email->getFrom()->getNameOrEmail();
        $date = $email->getDate()->format('D, d M Y H:i:s O');

        // Get the plain text content from the parsed email
        $originalContent = $parsedEmail->getTextContent();
        if ($originalContent) {
            // Quote each line with "> "
            $quotedContent = implode("\n", array_map(
                fn($line) => '> ' . $line,
                explode("\n", trim($originalContent))
            ));
        } else {
            $quotedContent = '';
        }

        $body = sprintf(
            "On %s, %s wrote:\n%s\n\n",
            $date,
            $fromName,
            $quotedContent
        );

        // Build the mailto URL with headers
        // Note: Not all email clients support custom headers via mailto
        $params = [
            'subject' => $subject,
            'body' => $body,
            'In-Reply-To' => $messageId,
            'References' => $newReferences,
        ];

        $queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return 'mailto:' . $to . '?' . $queryString;
    }
}
