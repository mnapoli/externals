<?php

declare(strict_types=1);

namespace App\Services\Rfc;

/**
 * Parses the DokuWiki source of https://wiki.php.net/rfc.
 *
 * The page lists RFCs in sections ("Under Discussion", "Implemented"…), some
 * of them split into subsections ("PHP 8.5"…). Each RFC is a list item that
 * starts with a link to its page.
 */
class RfcIndexParser
{
    /**
     * @return list<RfcIndexEntry> In the order of the page, without duplicates
     */
    public function parse(string $source): array
    {
        $entries = [];
        $status = null;
        $subsection = null;
        $position = 0;

        foreach (preg_split('/\R/', $source) ?: [] as $line) {
            if (preg_match('/^\s*={5}([^=].*?)={5}\s*$/', $line, $matches)) {
                $status = RfcStatus::fromIndexSection(mb_trim($matches[1]));
                $subsection = null;

                continue;
            }
            if (preg_match('/^\s*={4}([^=].*?)={4}\s*$/', $line, $matches)) {
                $subsection = mb_trim($matches[1]);

                continue;
            }
            if ($status === null || ! preg_match('/^\s+\* (.*)$/', $line, $matches)) {
                continue;
            }
            if (! preg_match('/\[\[\s*(rfc\s*[:\/][^\]|]*?)\s*(?:\|([^\]]*))?\]\]/i', $matches[1], $link)) {
                continue;
            }

            $slug = RfcLinkExtractor::normalizeSlug($link[1]);
            if ($slug === '' || isset($entries[$slug])) {
                continue;
            }

            $phpVersion = null;
            if ($status === RfcStatus::Implemented && $subsection !== null && preg_match('/^PHP\s+(\d+\.\d+)/i', $subsection, $version)) {
                $phpVersion = $version[1];
            }

            $title = mb_trim(str_replace("''", '', $link[2] ?? ''));

            $entries[$slug] = new RfcIndexEntry(
                slug: $slug,
                title: $title !== '' ? $title : $slug,
                status: $status,
                subsection: $subsection,
                phpVersion: $phpVersion,
                position: $position++,
            );
        }

        return array_values($entries);
    }
}
