<?php declare(strict_types=1);

namespace Externals\Email;

/**
 * Class imported from github.com/madewithlove/why-cant-we-have-nice-things because requiring the whole
 * package wouldn't really make sense (it's a whole application) and it would require *a lot* of extra
 * Composer dependencies for nothing.
 *
 * @see https://github.com/madewithlove/why-cant-we-have-nice-things
 * @see https://github.com/madewithlove/why-cant-we-have-nice-things/blob/master/src/Services/IdentityExtractor.php
 */
class EmailAddressParser
{
    public function __construct(
        private string $string,
        private array $emails = [],
        private array $names = [],
    ) {
    }

    /**
     * @return EmailAddress[]
     */
    public function parse(): array
    {
        // Workaround some anti-bot measures
        $emails = str_replace('(original)', '', $this->string);
        $emails = preg_replace('/[<>\(\)]/', '', $emails);
        $emails = str_replace(' . ', '.', $emails);

        $names = $this->extractEmails($emails);
        $this->extractNames($names);

        // Cleanup dead results
        $this->emails = array_values(array_filter($this->emails));
        $this->names = array_values(array_filter($this->names));

        // Combine informations
        $identities = [];
        $count = count($this->emails) ?: count($this->names);

        for ($i = 0; $i < $count; ++$i) {
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
        // Try to split off emails
        $names = $emails;
        $emails = preg_split('/[\s,\/]+/', $emails);

        foreach ($emails as $key => $email) {
            // Check if email is valid, if not
            // throw it away
            $email = $this->trimCharacters($email);
            $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
            $names = str_replace($email, '', $names);
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

            // Special cases for that one guy who
            // put his whole resume as name and other
            // marvelous joys
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
