<?php
declare(strict_types=1);

namespace Externals\Email;

/**
 * Class imported from github.com/madewithlove/why-cant-we-have-nice-things because requiring the whole
 * package wouldn't really make sense (it's a whole application) and it would require *a lot* of extra
 * Composer dependencies for nothing.
 *
 * @author Maxime Fabre https://github.com/Anahkiasen
 * @see https://github.com/madewithlove/why-cant-we-have-nice-things
 * @see https://github.com/madewithlove/why-cant-we-have-nice-things/blob/master/src/Services/IdentityExtractor.php
 */
class EmailAddressParser
{
    /**
     * @var string
     */
    private $string;

    /**
     * @var array
     */
    private $emails = [];

    /**
     * @var array
     */
    private $names = [];

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    /**
     * @return EmailAddress[]
     */
    public function parse()
    {
        // Workaround some anti-bot measures
        $emails = str_replace('(original)', '', $this->string);
        $emails = preg_replace('/[<>\(\)]/', '', $emails);
        $emails = str_replace(' . ', '.', $emails);
        $emails = preg_replace('/([. #]at[.# ])/', '@', $emails);
        $emails = preg_replace('/([. #]dot[.# ])/', '.', $emails);

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
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email = null;
            }

            $identities[] = new EmailAddress($email, $this->names[$i] ?? null);
        }

        return $identities;
    }

    /**
     * @param string $emails
     *
     * @return string
     */
    private function extractEmails($emails)
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

    /**
     * @return string
     */
    private function trimCharacters(string $string)
    {
        return trim($string, ' /"=');
    }

    /**
     * @param string $names
     */
    private function extractNames($names)
    {
        $names = preg_split('/(,|  |\n)/', $names);
        $names = array_filter($names);

        foreach ($names as $key => $name) {
            $name = $this->trimCharacters($name);

            // Special cases for that one guy who
            // put his whole resume as name and other
            // marvelous joys
            if (
                mb_strpos($name, 'Watson Research') ||
                mb_strlen($name) <= 3 ||
                mb_strpos($name, '?') !== false ||
                mb_strpos($name, 'http') !== false
            ) {
                continue;
            }

            $this->names[$key] = $name;
        }
    }
}
