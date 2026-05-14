<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Support\Email\EmailAddress;

/**
 * @see https://github.com/madewithlove/why-cant-we-have-nice-things/blob/master/src/Services/IdentityExtractor.php
 */
class EmailAddressParser
{
    private array $emails = [];

    private array $names = [];

    public function __construct(
        private readonly string $string,
    ) {}

    /**
     * @return EmailAddress[]
     */
    public function parse(): array
    {
        $emails = str_replace('(original)', '', $this->string);
        $emails = preg_replace('/[<>\(\)]/', '', $emails);
        $emails = str_replace(' . ', '.', $emails);

        $names = $this->extractEmails($emails);
        $this->extractNames($names);

        $this->emails = array_values(array_filter($this->emails));
        $this->names = array_values(array_filter($this->names));

        $identities = [];
        $count = count($this->emails) ?: count($this->names);

        for ($i = 0; $i < $count; $i++) {
            $email = $this->emails[$i] ?? null;
            if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email = null;
            }

            $identities[] = new EmailAddress($email, $this->names[$i] ?? null);
        }

        return $identities;
    }

    private function extractEmails(string $emails): string
    {
        $names = $emails;
        $emails = preg_split('/[\s,\/]+/', $emails);

        foreach ($emails as $key => $email) {
            $email = $this->trimCharacters($email);
            $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
            if ($email) {
                $names = str_replace($email, '', $names);
            }
            $this->emails[$key] = $email;
        }

        return $names;
    }

    private function trimCharacters(string $string): string
    {
        return trim($string, ' /"=');
    }

    private function extractNames(string $names): void
    {
        $names = preg_split('/(,|  |\n)/', $names);
        $names = array_filter($names);

        foreach ($names as $key => $name) {
            $name = $this->trimCharacters($name);

            if (mb_strpos($name, 'Watson Research') ||
                mb_strlen($name) <= 3 ||
                str_contains($name, '?') ||
                str_contains($name, 'http')
            ) {
                continue;
            }

            $this->names[$key] = $name;
        }
    }
}
