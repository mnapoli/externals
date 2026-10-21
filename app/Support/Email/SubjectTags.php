<?php

declare(strict_types=1);

namespace App\Support\Email;

/**
 * Splits the leading "[RFC] [VOTE]" tags off a thread subject, so that lists can show them as chips.
 */
final readonly class SubjectTags
{
    /**
     * @param  list<string>  $tags
     */
    private function __construct(
        public array $tags,
        public string $title,
    ) {}

    public static function parse(string $subject): self
    {
        $tags = [];
        $title = mb_trim($subject);
        while (preg_match('/^\[([^\]\[]{1,30})\]\s*(.*)$/su', $title, $matches) === 1) {
            $tags[] = mb_trim($matches[1]);
            $title = $matches[2];
        }

        // A subject made only of tags keeps them as its title
        if ($title === '') {
            return new self([], mb_trim($subject));
        }

        return new self($tags, $title);
    }

    /**
     * @return list<string>
     */
    public function tagsExcept(string ...$excluded): array
    {
        $excluded = array_map(mb_strtolower(...), $excluded);

        return array_values(array_filter($this->tags, fn(string $tag) => ! in_array(mb_strtolower($tag), $excluded, true)));
    }
}
