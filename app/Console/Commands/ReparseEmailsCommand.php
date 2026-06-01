<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Email;
use App\Services\Email\EmailContentParser;
use Illuminate\Console\Command;
use ZBateson\MailMimeParser\MailMimeParser;

class ReparseEmailsCommand extends Command
{
    protected $signature = 'externals:reparse';
    protected $description = 'Re-parse the content of every email from its stored source';

    public function handle(EmailContentParser $parser): int
    {
        $start = microtime(true);
        $maxNumber = (int) Email::max('number');
        $mailParser = new MailMimeParser;

        for ($number = $maxNumber; $number > 0; $number--) {
            $email = Email::where('number', $number)->first();
            if (! $email) {
                continue;
            }
            $parsedDocument = $mailParser->parse($email->source, false);
            $email->content = $parser->parse((string) $parsedDocument->getTextContent());
            $email->save();
            $this->info("Updated email $number");
        }

        $time = microtime(true) - $start;
        $this->comment(sprintf('Emails have been reparsed in %.2f seconds', $time));

        return self::SUCCESS;
    }
}
