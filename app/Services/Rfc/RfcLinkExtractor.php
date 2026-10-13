<?php

declare(strict_types=1);

namespace App\Services\Rfc;

/**
 * Finds links to RFC pages of wiki.php.net in a text.
 */
class RfcLinkExtractor
{
    /**
     * An email that links to more RFCs than this is a roundup (release
     * announcement, newsletter…), not the discussion of an RFC.
     */
    public const int MAX_RFCS_PER_THREAD = 5;

    /**
     * Normalizes a DokuWiki page ID (as written in links) into an RFC slug.
     *
     * "rfc:Foo Bar", "rfc/foo_bar" and "rfc::foo_bar" all become "foo_bar".
     */
    public static function normalizeSlug(string $pageId): string
    {
        $slug = mb_strtolower(rawurldecode($pageId));
        $slug = (string) preg_replace('#^\s*rfc\s*[:/]#', '', $slug);
        $slug = str_replace(':', '/', mb_trim($slug));
        $slug = (string) preg_replace('/\s+/', '_', $slug);

        return mb_trim($slug, '/._-');
    }

    /**
     * @return list<string> RFC slugs, without duplicates
     */
    public function extract(string $text): array
    {
        preg_match_all(
            '#wiki\.php\.net/(?:doku\.php\?id=)?rfc(?:[/:]|%3a)([a-z0-9_.:/%-]+)#i',
            $text,
            $matches,
        );

        $slugs = [];
        foreach ($matches[1] as $pageId) {
            $slug = self::normalizeSlug($pageId);
            if ($slug !== '' && ! in_array($slug, ['howto', 'template'], true)) {
                $slugs[$slug] = true;
            }
        }

        return array_keys($slugs);
    }

    /**
     * The RFCs discussed by a thread, from the content of its root email.
     *
     * @return list<string>
     */
    public function extractFromThreadRoot(string $content): array
    {
        $slugs = $this->extract($content);

        return count($slugs) > self::MAX_RFCS_PER_THREAD ? [] : $slugs;
    }
}
