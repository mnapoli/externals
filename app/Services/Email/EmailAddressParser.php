<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Support\Email\EmailAddress;
use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\Header\IHeader;

/**
 * Parses the loose addresses found in old emails: "john@example.com (John Doe)", "john . doe@example.com"…
 *
 * @see https://github.com/madewithlove/why-cant-we-have-nice-things/blob/master/src/Services/IdentityExtractor.php
 */
class EmailAddressParser
{
    /** @var array<int, ?string> */
    private array $emails = [];

    /** @var array<int, string> */
    private array $names = [];

    public function __construct(
        private readonly string $string,
    ) {}

    /**
     * The sender of an email. The mail parser decodes names ("=?UTF-8?Q?…?=") and quoted names
     * ("Doe, John"), and this parser fills in what it misses, e.g. names written as a comment.
     * The address comes from this parser first: the mail parser merges an unbracketed name into it.
     */
    public static function sender(IHeader $from): ?EmailAddress
    {
        $loose = (new self($from->getRawValue()))->parse()[0] ?? null;
        $email = $from instanceof AddressHeader ? $from->getEmail() : null;
        $name = $from instanceof AddressHeader ? mb_trim((string) $from->getPersonName()) : '';

        $email = $loose->email ?? (filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null);
        $name = $name !== '' ? $name : $loose?->name;
        if ($name === $email) {
            $name = null;
        }
        if ($email === null && $name === null) {
            return null;
        }

        return new EmailAddress($email, $name);
    }

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
        $emails = preg_split('/[\s,\/]+/', $emails) ?: [];

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
        return mb_trim($string, ' /"=');
    }

    private function extractNames(string $names): void
    {
        $names = preg_split('/(,|  |\n)/', $names) ?: [];
        $names = array_filter($names);

        foreach ($names as $key => $name) {
            $name = $this->trimCharacters($name);

            if (mb_strpos($name, 'Watson Research')
                || mb_strlen($name) <= 3
                || str_contains($name, '?')
                || str_contains($name, 'http')
            ) {
                continue;
            }

            $this->names[$key] = $name;
        }
    }
}
