<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Email\EmailContentParser;
use App\Email\EmailRepository;
use App\Exceptions\NotFoundException;
use Illuminate\Console\Command;
use ZBateson\MailMimeParser\MailMimeParser;

class ReparseEmailsCommand extends Command
{
    protected $signature = 'externals:reparse';

    protected $description = 'Re-parse the content of every email from its stored source';

    public function handle(EmailRepository $repository, EmailContentParser $parser): int
    {
        $start = microtime(true);
        $maxNumber = $repository->getLastEmailNumber();
        $mailParser = new MailMimeParser;

        for ($number = $maxNumber; $number > 0; $number--) {
            try {
                $email = $repository->getByNumber($number);
            } catch (NotFoundException) {
                continue;
            }
            $parsedDocument = $mailParser->parse($email->source, false);
            $content = $parser->parse((string) $parsedDocument->getTextContent());
            $repository->updateContent($email->id, $content);
            $this->info("Updated email $number");
        }

        $time = microtime(true) - $start;
        $this->comment(sprintf('Emails have been reparsed in %.2f seconds', $time));

        return self::SUCCESS;
    }
}
